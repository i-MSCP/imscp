<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DNS Library for handling lookups and updates. 
 *
 * PHP Version 5
 *
 * Copyright (c) 2010, Mike Pultz <mike@mikepultz.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Mike Pultz nor the names of his contributors 
 *     may be used to endorse or promote products derived from this 
 *     software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2010 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id: Resolver.php 137 2011-12-05 02:51:34Z mike.pultz $
 * @link      http://pear.php.net/package/Net_DNS2
 * @since     File available since Release 0.6.0
 *
 */

/**
 * This is the main resolver class, providing DNS query functions.
 *
 * @category Networking
 * @package  Net_DNS2
 * @author   Mike Pultz <mike@mikepultz.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://pear.php.net/package/Net_DNS2
 * @see      Net_DNS2
 *
 */
class Net_DNS2_Resolver extends Net_DNS2
{
    /**
     * Constructor - creates a new Net_DNS2_Resolver object
     *
     * @param mixed $options either an array with options or null
     *
     * @access public
     *
     */
    public function __construct(array $options = null)
    {
        parent::__construct($options);
    }

    /**
     * does a basic DNS lookup query
     *
     * @param string $name  the DNS name to loookup
     * @param string $type  the name of the RR type to lookup
     * @param string $class the name of the RR class to lookup
     *
     * @return Net_DNS_RR object
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function query($name, $type = 'A', $class = 'IN')
    {
        //
        // make sure we have some name servers set
        //
        $this->checkServers(Net_DNS2::RESOLV_CONF);

        //
        // we dont' support incremental zone tranfers; so if it's requested, a full
        // zone transfer can be returned
        //
        if ($type == 'IXFR') {

            $type = 'AXFR';
        }

        //
        // if the name *looks* too short, then append the domain from the config
        //
        if ( (strpos($name, '.') === false) && ($type != 'PTR') ) {

            $name .= '.' . strtolower($this->domain);
        }

        //
        // create a new packet based on the input
        //
        $packet = new Net_DNS2_Packet_Request($name, $type, $class);

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG)
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $packet->additional[]       = $this->auth_signature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // if caching is turned on, then check then hash the question, and
        // do a cache lookup.
        //
        $packet_hash = '';

        if ($this->use_cache == true) {
    
            //
            // open the cache
            //
            $this->cache->open($this->cache_file, $this->cache_size, $this->cache_serializer);

            //
            // build the key and check for it in the cache.
            //
            $packet_hash = md5(
                $packet->question[0]->qname . '|' . $packet->question[0]->qtype
            );

            if ($this->cache->has($packet_hash)) {

                return $this->cache->get($packet_hash);
            }
        }

        //
        // send the packet and get back the response
        //
        $response = $this->sendPacket(
            $packet, ($type == 'AXFR') ? true : $this->use_tcp
        );

        if ($this->use_cache == true) {

            $this->cache->put($packet_hash, $response);
        }

        return $response;
    }

    /**
     * does an inverse query for the given RR; most DNS servers do not implement 
     * inverse queries, but they should be able to return "not implemented"
     *
     * @param Net_DNS2_RR $rr the RR object to lookup
     * 
     * @return Net_DNS_RR object
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function iquery(Net_DNS2_RR $rr)
    {
        //
        // make sure we have some name servers set
        //
        $this->checkServers(Net_DNS2::RESOLV_CONF);

        //
        // create an empty packet
        //
        $packet = new Net_DNS2_Packet_Request($rr->name, 'A', 'IN');

        //
        // unset the question
        //
        $packet->question = array();
        $packet->header->qdcount = 0;

        //
        // set the opcode to IQUERY
        //
        $packet->header->opcode = Net_DNS2_Lookups::OPCODE_IQUERY;

        //
        // add the given RR as the answer
        //
        $packet->answer[] = $rr;
        $packet->header->ancount = 1;

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG)
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $packet->additional[]       = $this->auth_signature;
            $packet->header->arcount    = count($packet->additional);
        }

        //
        // send the packet and get back the response
        //
        return $this->sendPacket($packet, $this->use_tcp);
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
