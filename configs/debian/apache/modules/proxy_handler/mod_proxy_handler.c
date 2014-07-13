/*
 * Copyright 2014 Andreas Bosch
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/
#include "httpd.h"
#include "http_config.h"
#include "http_protocol.h"
#include "ap_config.h"
#include "apr_strings.h"
#include "http_request.h"

static int proxy_handler_handler(request_rec *r)
{
    // This function is adapted from a patch to mod_proxy
    // http://svn.apache.org/viewvc?view=revision&revision=1573626
    if (r->filename && !r->proxyreq) {
        /* We may have forced the proxy handler via config or .htaccess */
        if (r->handler &&
            strncmp(r->handler, "proxy:", 6) == 0 &&
            strncmp(r->filename, "proxy:", 6) != 0) {
                r->proxyreq = PROXYREQ_REVERSE;
                r->filename = apr_pstrcat(r->pool, r->handler, r->filename, NULL);
                apr_table_setn(r->notes, "rewrite-proxy", "1");
                r->handler = "proxy-server";
                // always return declined since we do some weird stuff:
                // we modify the request in a handler
                return DECLINED;
        }
    }

    return DECLINED;
}

static void proxy_handler_register_hooks(apr_pool_t *p)
{
    static const char * const aszSucc[] = { "mod_proxy.c", NULL };
    ap_hook_handler(proxy_handler_handler, NULL, aszSucc, APR_HOOK_REALLY_FIRST);
}

/* Dispatch list for API hooks */
module AP_MODULE_DECLARE_DATA proxy_handler_module = {
    STANDARD20_MODULE_STUFF,
    NULL, /* create per-dir config structures */
    NULL, /* merge per-dir config structures */
    NULL, /* create per-server config structures */
    NULL, /* merge per-server config structures */
    NULL, /* table of config file commands */
    proxy_handler_register_hooks /* register hooks */
};
