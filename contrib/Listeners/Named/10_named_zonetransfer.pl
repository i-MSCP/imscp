# i-MSCP Listener::Named::Zonetransfer listener file
# Copyright (C) 2015 UncleJ, Arthur Mayer <mayer.arthur@gmail.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## i-MSCP listener file provides zone output for zone transfer to secondary DNS
#

package Listener::Named::Zonetransfer;

use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Config;
use iMSCP::Dir;

# $secondaryDnsUsername: user name to access protected transfer script, if similar user should be defined in .htpasswd
# file its credentials will be kept and used - leave empty if no authentication method to be used
my $secondaryDnsUsername = 'htuser';

# $secondaryDnsPassword: either encrypted or decrypted password to access protected transfer script - if similar user to
# $secondaryDnsUsername should be defined in .htpasswd file its credentials will be kept and used, otherwise just needed
# if authentication method to be used
my $secondaryDnsPassword = 'htpass';

# $secondaryDnsPasswordEncrypted: 0 - provided password $secondaryDnsPassword is decrypted
# 1 - provided password $secondaryDnsPassword is encrypted - if similar user to $secondaryDnsUsername should be defined
# in .htpasswd file its credentials will be kept and used, otherwise just needed if authentication method to be used
my $secondaryDnsPasswordEncrypted = 0;

# $nameOfProtectedArea: name of protected area to be displayed - if empty file name of transfer script to be displayed -
# just needed if authentication method to be used
my $nameOfProtectedArea = "Secondary DNS service";

# $htpasswdFilePath: path of .htpasswd file to use for authentication on accessing transfer script -  just needed if
# authentication method to be used
my $htpasswdFilePath = $main::imscpConfig{'GUI_PUBLIC_DIR'} . '/domain/.htpasswd';

# $transferScriptFilePath: name of transfer script file - if empty the transfer script will be stored as
# $main::imscpConfig{'GUI_PUBLIC_DIR'} . '/domain/index.php'
my $transferScriptFilePath = $main::imscpConfig{'GUI_PUBLIC_DIR'} . '/domain/index.php';

# $overwriteFileIfTransferScriptExists: only used if $transferScriptFilePath not empty otherwise interpreted as ´0,
# 0 - if another file at $transferScriptFilePath already exists the file will be kept,
# 1 - even if another file at $transferScriptFilePath already exists the new transfer script will override it
my $overwriteFileIfTransferScriptExists = 1;

# $setAlsoProtectionIfTransferScriptExists: only used if $transferScriptFilePath not empty otherwise interpreted as ´0,
# 0 - if another file at $transferScriptFilePath already exists the file protection won't be added,
# 1 - even if another file at $transferScriptFilePath already exists the file protection will be set for the file
my $setAlsoProtectionIfTransferScriptExists = 1;

# $setOnlyAccessableBySecondaryDnsServer: 0 - transfer script is accessible by any host,
# 1 - transfer script is only accessible by hosts identified through IP addresses define by parameter
# $secondaryDnsConfParameter in configuration file $bindDataFilePath
my $setOnlyAccessableBySecondaryDnsServer = 1;

# $bindDataFilePath: absolute path of configuration file where parameter $secondaryDnsConfParameter is defined - just
# needed if transfer script should just be accessible by secondary DNS servers
my $bindDataFilePath = '/etc/imscp/bind/bind.data';

# $secondaryDnsConfParameter: name of parameter inside configuration file $bindDataFilePath which defines IP addresses
# of secondary DNS servers just needed if transfer script should just be accessible by secondary DNS servers
my $secondaryDnsConfParameter = 'SECONDARY_DNS';

# $manualSecondaryDnsServers: white space separated list of IP addresses for secondary DNS servers which shall be added
# to list of allowed hosts just needed if transfer script should just be accessible by secondary DNS servers
my $manualSecondaryDnsServers = '';

# $overwriteSecondaryDnsServers: only used if $transferScriptFilePath not empty and $setOnlyAccessableBySecondaryDnsServer
# set to 1 and if $manualSecondaryDnsServers contains valid IP addresses of secondardy DNS servers
# 0 - allow access to transfer script file only to hosts defined by $manualSecondaryDnsServers
# 1 - hosts defined by $manualSecondaryDnsServers are added to the ones found in parameter $secondaryDnsConfParameter in
# the configuration file defined under $bindDataFilePath
my $overwriteSecondaryDnsServers = 0;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('afterInstall', sub {
    my $rs = 0;

    if(length($transferScriptFilePath) == 0) {
        $transferScriptFilePath = $main::imscpConfig{'GUI_PUBLIC_DIR'} . '/domain/index.php';
        $overwriteFileIfTransferScriptExists = 0;
        $setAlsoProtectionIfTransferScriptExists = 0;
    }

    my $transferScriptFolder = substr(
        $transferScriptFilePath, 0, (length($transferScriptFilePath) - index(reverse($transferScriptFilePath), '/'))
    );

    if(length($htpasswdFilePath) == 0) {
        $htpasswdFilePath = $transferScriptFolder . '.htpasswd';
    }

    my $transferScriptFileName = substr($transferScriptFilePath, length($transferScriptFolder));

    sub createFileProtection 
    {
        my $htaccessFile;
        my $htaccessFilePath = $transferScriptFolder . '.htaccess';
        my $secondaryDnsServers = '';
        my @secondaryDnsServersIp;
        
        sub handleHtpasswdFile 
        {
            my $htpasswdFileContent;
            my $htpasswdFile;
            
            $htpasswdFile = iMSCP::File->new( filename => $htpasswdFilePath );

            unless (-f $htpasswdFilePath) {
                if($secondaryDnsPasswordEncrypted) {
                    $htpasswdFile->set("$secondaryDnsUsername:$secondaryDnsPassword\n");
                    $htpasswdFile->save();
                } else {
                    $rs =  system("htpasswd -cb \"$htpasswdFilePath\" \"$secondaryDnsUsername\" \"$secondaryDnsPassword\"");
                }

                return $rs if $rs;
            } else {
                $htpasswdFileContent = $htpasswdFile->get();

                my $credentials = $htpasswdFileContent;

                unless($credentials =~ /^[\t\s]*$secondaryDnsUsername:.*$/m) {
                    if($secondaryDnsPasswordEncrypted) {
                        $htpasswdFileContent .= "$secondaryDnsUsername:$secondaryDnsPassword\n";
                        $htpasswdFile->set($htpasswdFileContent);
                        $htpasswdFile->save();
                    } else {
                        $rs = system("htpasswd -b \"$htpasswdFilePath\" \"$secondaryDnsUsername\" \"$secondaryDnsPassword\"");
                    }

                    return $rs if $rs;
                }
            }

            $htpasswdFile->mode(0640);
            $htpasswdFile->owner(
                "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
                "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}"
            );
        }

        sub createHtaccessSection 
        {        
            sub is_ipv4Adrress 
            {    
                my $ipv4Address = shift;

                if($ipv4Address =~ /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/ && (( $1 <= 255  && $2 <= 255 && $3 <= 255 && $4 <= 255 ))) {
                    return 1;
                } else {
                    return 0;
                }
            }
            
            my $htaccessSection = "<Files $transferScriptFileName>\n";
            
            if(length($secondaryDnsUsername) > 0 && (-f $htpasswdFilePath)) {
                $htaccessSection .= "\tAuthType Basic\n";

                if(length($nameOfProtectedArea)>0) {
                    $htaccessSection .= "\tAuthName \"$nameOfProtectedArea\"\n";
                } else {
                    $htaccessSection .= "\tAuthName \"$transferScriptFileName\"\n";
                }

                $htaccessSection .= "\tAuthUserFile $htpasswdFilePath\n";
            }
            
            if($setOnlyAccessableBySecondaryDnsServer == 1) {
                if(length($manualSecondaryDnsServers) > 0) {
                    my @tokens = split /[\s\t]+/, $manualSecondaryDnsServers;

                    for my $token(@tokens) {
                        if(is_ipv4Adrress($token)) {
                            push(@secondaryDnsServersIp,$token);
                        }
                    }
                } else {
                    $overwriteSecondaryDnsServers = 0;
                }
                
                if(
                    (!$overwriteSecondaryDnsServers || @secondaryDnsServersIp == 0) &&
                    length($bindDataFilePath) > 0 && length($secondaryDnsConfParameter) > 0
                ) {
                    unless(-f $bindDataFilePath){
                        debug("access should be enabled only for secondary DNS servers but configuration file for secondary DNS servers could not be found at $bindDataFilePath");
                    } else {
                        my $bindDataFile = iMSCP::Config->new( fileName => $bindDataFilePath );

                        if($bindDataFile->EXISTS($secondaryDnsConfParameter)) {
                            $secondaryDnsServers .=  $bindDataFile->FETCH($secondaryDnsConfParameter);
                        } else {
                            debug("access should be enabled only for secondary DNS servers but configuration file for secondary DNS servers at $bindDataFilePath does not contain parameter $secondaryDnsConfParameter");
                        }
                    }
                }

                my @tokens = split /[;\s\t]+/, $secondaryDnsServers;

                for my $token(@tokens) {
                    if(is_ipv4Adrress($token)) {
                        push(@secondaryDnsServersIp,$token);
                    }
                }

                if(@secondaryDnsServersIp > 0){
                    $htaccessSection .= "\tOrder Deny,Allow\n\tDeny from all\n";

                    for my $secondaryDnsServerIp(@secondaryDnsServersIp) {
                        $htaccessSection .= "\tAllow from $secondaryDnsServerIp\n";
                    }
                } else {
                    debug("access should be enabled only for secondary DNS servers but no server IP addresses found, (n)either in configuration (n)or in \$manualSecondaryDnsServers , depending on configuration");
                    debug(".htaccess to be created without access limitation of accessing host");
                }
            }

            if(length($secondaryDnsUsername) > 0 && -f $htpasswdFilePath) {
                $htaccessSection .= "\tRequire user $secondaryDnsUsername\n";
            }

            $htaccessSection .= "\tSatisfy all\n</Files>\n";
            return $htaccessSection;
        }

        $htaccessFile = iMSCP::File->new( filename => $htaccessFilePath );

        unless (-f $htaccessFilePath) {
            if(length($secondaryDnsUsername)>0) {
                $rs = handleHtpasswdFile();
                return $rs if $rs;
            }

            $htaccessFile->set(createHtaccessSection());
        } else {
            my $htaccessFileContent = $htaccessFile->get();
            my $directive = $htaccessFileContent;
            my $directiveIntroduction;
            my $directiveAppendix;
            my $gap;

            if($directive !~ /^(([\s\t]*)\<[Ff][Ii][Ll][Ee][Ss][\s\t]+$transferScriptFileName\>)(((.*\n)*).*)(\n[\s\t]*\<\/[Ff][Ii][Ll][Ee][Ss]\>.*)$/m) {
                if(length($secondaryDnsUsername) > 0) {
                    $rs = handleHtpasswdFile();
                    return $rs if $rs;
                }

                $htaccessFileContent .= "\n".createHtaccessSection();
            } else {
                $directive = $3;
                $directiveIntroduction = $1;
                $directiveAppendix = $6;
                $gap = $2;

                if(length($secondaryDnsUsername) > 0) {
                    if($directive !~ /^[\s\t]*AuthType.*$/m) {
                        debug("no AuthType found");
                        $directive = "$gap\tAuthType Basic\n$directive";

                        if(length($nameOfProtectedArea) > 0) {
                            $directive = "$gap\tAuthName \"$nameOfProtectedArea\"\n$directive";
                        } else {
                            $directive = "$gap\tAuthName \"$transferScriptFileName\"\n$directive";
                        }

                        $directive = "$gap\tAuthUserFile $htpasswdFilePath\n$directive";
                        $directive = "$gap\tRequire user $secondaryDnsUsername\n$directive";
                        $directive =~ s/^([\s\t]*Satisfy[\s\t]*)any(.*)$/$1all$2/m;

                        $rs = handleHtpasswdFile();
                        return $rs if $rs;

                    } else {
                        unless($directive =~ /^[\s\t]*AuthType[\s\t]*Basic.*$/m) {
                            debug("Not supported AuthType found in .htaccess file at $htaccessFilePath\. Please set access protection for transfer script at $transferScriptFilePath manually.");
                        } else {
                            debug("AuthType Basic found");

                            unless($directive =~ /^[\s\t]*AuthUserFile[\s\t]*([\/0-9a-zA-Z]*\/\.htpasswd).*$/m) {
                                debug("no path to .htpasswd file specified in existing directive in .htaccess file at $htaccessFilePath\. Please set access protection for transfer script at $transferScriptFilePath manually.");
                            } else {
                                $htpasswdFilePath = $1;
                            }

                            $rs = handleHtpasswdFile();
                            return $rs if $rs;

                            if(
                                $directive =~ /^([\s\t]*Require[\s\t]*user[\s\t]*)(([0-9a-zA-Z]*[\s\t]*)*)(.*)$/m &&
                                $directive !~ /^[\s\t]*Require[\s\t]*user([\s\t]*[0-9a-zA-z]*)+[\s\t]*$secondaryDnsUsername[\s\t]*.*$/m
                            ) {
                                $directive =~ s/^([\s\t]*Require[\s\t]*user[\s\t]*)(([0-9a-zA-Z]*[\s\t]*)*)(.*)$/$1$secondaryDnsUsername $2$4/m;
                            }
                        }
                    }
                }
                
                if($setOnlyAccessableBySecondaryDnsServer == 1) {
                    if(length($manualSecondaryDnsServers) > 0) {
                        my @tokens = split /[\s\t]+/, $manualSecondaryDnsServers;

                        for my $token(@tokens) {
                            if(is_ipv4Adrress($token)) {
                                push(@secondaryDnsServersIp,$token);
                            }
                        }
                    } else {
                        $overwriteSecondaryDnsServers = 0;
                    }
                    
                    if((!$overwriteSecondaryDnsServers || @secondaryDnsServersIp == 0) && length($bindDataFilePath) > 0 && length($secondaryDnsConfParameter) > 0) {
                        if(!(-f $bindDataFilePath)){
                            debug("access should be enabled only for secondary DNS servers but configuration file for secondary DNS servers could not be found at $bindDataFilePath");
                        } else {
                            my $bindDataFile = iMSCP::Config->new( fileName => $bindDataFilePath );

                            if($bindDataFile->EXISTS($secondaryDnsConfParameter)) {
                                $secondaryDnsServers .=  $bindDataFile->FETCH($secondaryDnsConfParameter);
                            } else {
                                debug("access should be enabled only for secondary DNS servers but configuration file for secondary DNS servers at $bindDataFilePath does not contain parameter $secondaryDnsConfParameter");
                            }
                        }
                    }

                    my @tokens = split /[\s\t]+/, $secondaryDnsServers;

                    for my $token(@tokens) {
                        if(is_ipv4Adrress($token)) {
                            push(@secondaryDnsServersIp,$token);
                        }
                    }

                    if(@secondaryDnsServersIp > 0){
                        if($directive !~ /^[\s\t]*Order[\s\t]*((Allow,[\s\t]*Deny)|(Deny,[\s\t]*Allow)).*$/m) {
                            debug("no Order directive found");

                            # TO-DO: write list of allowed IP addresses if any exist
                            $directive .= "\n$gap\tOrder Deny,Allow\n\tDeny from all\n";

                            for my $secondaryDnsServerIp(@secondaryDnsServersIp) {
                                $directive .= "$gap\tAllow from $secondaryDnsServerIp\n";
                            }

                            $directive = ~s/^([\s\t]*Satisfy )any(.*)$/$1all$2/m;
                        } else {
                            debug("Order directive found in .htaccess file at $htaccessFilePath\. Please set access protection for transfer script at $transferScriptFilePath manually.");
                        }
                    } else {
                        debug("access should be enabled only for secondary DNS servers but no server IP addresses found, (n)either in configuration (n)or in \$manualSecondaryDnsServers , depending on configuration");
                        debug(".htaccess to be created without access limitation of accessing host");
                    }
                }

                if($directive !~ /^[\s\t]*Satisfy.*$/m {
                    $directive = "$gap\tSatisfy all\n$directive";
                }

                $htaccessFileContent =~ s/^(([\s\t]*)\<[Ff][Ii][Ll][Ee][Ss][\s\t]+$transferScriptFileName\>)(((.*\n)*).*)(\n[\s\t]*\<\/[Ff][Ii][Ll][Ee][Ss]\>.*)$/$directiveIntroduction$directive$directiveAppendix/m;
            }

            $htaccessFile->set($htaccessFileContent);
        }

        $htaccessFile->save();
        $htaccessFile->mode(0640);
        $htaccessFile->owner(
            "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
            "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}"
        );
    }

    sub writeTransferScript 
    {

        my $fileContent = shift;
        $$fileContent = <<'EOF';
<?php

require '../../library/imscp-lib.php';

$cfg = iMSCP_Registry::get('config');

echo "//CONFIGURATION FOR MAIN DOMAIN\n";
echo "zone \"$cfg->BASE_SERVER_VHOST\"{\n";
echo "\ttype slave;\n";
echo "\tfile \"/var/cache/bind/$cfg->BASE_SERVER_VHOST.db\";\n";
echo "\tmasters { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
echo "\tallow-notify { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
echo "};\n";
echo "//END CONFIGURATION FOR MAIN DOMAIN\n\n";

$stmt = execute_query("SELECT `domain_id`,`domain_name` FROM `domain`");
$countRecords = $stmt->rowCount();

if (!$countRecords) {
        echo "//NO DOMAINS LISTED\n";
} else {
    echo "//$countRecords HOSTED DOMAINS LISTED ON $cfg->SERVER_HOSTNAME [$cfg->BASE_SERVER_PUBLIC_IP]\n";

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)){
        echo "zone \"" . $row['domain_name']."\"{\n";
        echo "\ttype slave;\n";
        echo "\tfile \"/var/cache/bind/" . $row['domain_name'].".db\";\n";
        echo "\tmasters { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
        echo "\tallow-notify { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
        echo "};\n";
    }

    echo "//END DOMAINS LIST\n\n";
}

$stmt = execute_query('SELECT `alias_id`,`alias_name` FROM `domain_aliasses`');

$countRecords = $stmt->rowCount();
if (!$countRecords) {
        echo "//NO ALIASSES LISTED\n";
} else {
    echo "//$countRecords HOSTED ALIASSES LISTED ON $cfg->SERVER_HOSTNAME [$cfg->BASE_SERVER_PUBLIC_IP]\n";

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        echo "zone \"" . $row['alias_name'] . "\"{\n";
        echo "\ttype slave;\n";
        echo "\tfile \"/var/cache/bind/" . $row['alias_name'] . ".db\";\n";
        echo "\tmasters { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
        echo "\tallow-notify { $cfg->BASE_SERVER_PUBLIC_IP; };\n";
        echo "};\n";
    }

    echo "//END ALIASSES LIST\n";
}

?>
EOF
    0;

        my $transferScriptFile;
        $transferScriptFile = iMSCP::File->new( filename => $transferScriptFilePath );
        $transferScriptFile->set($$fileContent);
        $transferScriptFile->save();
        $transferScriptFile->owner(
            "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
            "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}"
        );
        $transferScriptFile->mode(0640);
    }
    
    if(! -d $transferScriptFolder) {
        my $scriptFolder =  iMSCP::Dir->new( dirname => $transferScriptFolder )->make({
            user => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
            group => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
            mode => 0550
        });
    }
    
    if($overwriteFileIfTransferScriptExists || ! -f $transferScriptFilePath) {
        $rs = createFileProtection();
        return $rs if $rs;
        $rs = writeTransferScript();
        return $rs if $rs;
    } elsif ($setAlsoProtectionIfTransferScriptExists) {
        $rs = createFileProtection();
        return $rs if $rs;
    }

    0;
});

1;
__END__
