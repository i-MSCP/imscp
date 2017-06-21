#!/bin/sh
# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

set -e

# Fix for https://bz.apache.org/bugzilla/show_bug.cgi?id=55329
# Fix for https://bz.apache.org/bugzilla/show_bug.cgi?id=55415
# diff -Naur mod_proxy_fcgi.c imscp_mod_proxy_fcgi.c
#
# Howto remove divert:
#  service apache2 stop
#  rm /usr/lib/apache2/modules/mod_proxy_fcgi.so
#  dpkg-divert --rename --remove /usr/lib/apache2/modules/mod_proxy_fcgi.so

APACHE_INSTALLED_VERSION=$(dpkg-query --show --showformat '${Version}' apache2)

service apache2 stop

# Remove divert if any
if [ -f /usr/lib/apache2/modules/mod_proxy_fcgi.so-DIST ] ; then
    rm -f rm /usr/lib/apache2/modules/mod_proxy_fcgi.so
    dpkg-divert --rename --remove /usr/lib/apache2/modules/mod_proxy_fcgi.so
fi

# Don't process if Apache2 version is ge 2.4.24
if dpkg --compare-versions "$APACHE_INSTALLED_VERSION" ge "2.4.24" ; then
    exit;
fi

rm -fR /tmp/imscp_apache2_src*
SRC_DIR=$(mktemp -p /tmp -d imscp_apache2_src.XXXXXX)

# Fix `W: Download is performed unsandboxed as root as file...' warning with
# newest APT versions
if id "_apt" >/dev/null 2>&1; then
    chown _apt ${SRC_DIR}
fi

cd ${SRC_DIR}

apt-src --quiet remove apache2
apt-src --quiet install apache2

APACHE_SOURCE_VERSION=$(apt-src version apache2)

# We must check for version mismatch between installed apache2 package and
# source package
if dpkg --compare-versions "$APACHE_SOURCE_VERSION" ne "$APACHE_INSTALLED_VERSION" ; then
    echo "There is a version mismatch between installed apache2 package and" 1>&2
    echo "apache2 source package. Please check your APT sources.list." 1>&2
    echo "" 1>&2
    echo "Both deb and deb-src repositories must provide the same version." 1>&2
    exit 1;
fi

cd apache2*/modules/proxy

# Patch for https://bz.apache.org/bugzilla/show_bug.cgi?id=55415
patch -p0 <<EOF
--- mod_proxy_fcgi.c	2016-10-09 01:05:17.000000000 +0200
+++ imscp_mod_proxy_fcgi.c	2016-10-09 01:20:38.711978843 +0200
@@ -591,7 +593,8 @@
                             }
 
                             if (conf->error_override &&
-                                ap_is_HTTP_ERROR(r->status)) {
+                                ap_is_HTTP_ERROR(r->status) &&
+                                ap_is_initial_req(r)) {
                                 /*
                                  * set script_error_status to discard
                                  * everything after the headers
EOF

if dpkg --compare-versions "$APACHE_INSTALLED_VERSION" lt "2.4.11" ; then
    # Patch for https://bz.apache.org/bugzilla/show_bug.cgi?id=55329
    patch -p0 <<EOF
--- mod_proxy_fcgi.c	2017-06-13 10:24:54.008100497 +0200
+++ imscp_mod_proxy_fcgi.c	2017-06-13 10:44:36.222194904 +0200
@@ -20,6 +20,10 @@
 
 module AP_MODULE_DECLARE_DATA proxy_fcgi_module;
 
+typedef struct {
+    int need_dirwalk;
+} fcgi_req_config_t;
+
 /*
  * Canonicalise http-like URLs.
  * scheme is the scheme for the URL
@@ -29,8 +33,11 @@
 static int proxy_fcgi_canon(request_rec *r, char *url)
 {
     char *host, sport[7];
-    const char *err, *path;
+    const char *err;
+    char *path;
     apr_port_t port, def_port;
+    fcgi_req_config_t *rconf = NULL;
+    const char *pathinfo_type = NULL;
 
     if (strncasecmp(url, "fcgi:", 5) == 0) {
         url += 5;
@@ -76,11 +83,51 @@
     ap_log_rerror(APLOG_MARK, APLOG_DEBUG, 0, r, APLOGNO(01060)
                   "set r->filename to %s", r->filename);
 
-    if (apr_table_get(r->subprocess_env, "proxy-fcgi-pathinfo")) {
-        r->path_info = apr_pstrcat(r->pool, "/", path, NULL);
+    rconf = ap_get_module_config(r->request_config, &proxy_fcgi_module);
+    if (rconf == NULL) {
+        rconf = apr_pcalloc(r->pool, sizeof(fcgi_req_config_t));
+        ap_set_module_config(r->request_config, &proxy_fcgi_module, rconf);
+    }
 
-        ap_log_rerror(APLOG_MARK, APLOG_DEBUG, 0, r, APLOGNO(01061)
-                      "set r->path_info to %s", r->path_info);
+    if (NULL != (pathinfo_type = apr_table_get(r->subprocess_env, "proxy-fcgi-pathinfo"))) {
+        /* It has to be on disk for this to work */
+        if (!strcasecmp(pathinfo_type, "full")) {
+            rconf->need_dirwalk = 1;
+            ap_unescape_url_keep2f(path, 0);
+        }
+        else if (!strcasecmp(pathinfo_type, "first-dot")) {
+            char *split = ap_strchr(path, '.');
+            if (split) {
+                char *slash = ap_strchr(split, '/');
+                if (slash) {
+                    r->path_info = apr_pstrdup(r->pool, slash);
+                    ap_unescape_url_keep2f(r->path_info, 0);
+                    *slash = '\0'; /* truncate path */
+                }
+            }
+        }
+        else if (!strcasecmp(pathinfo_type, "last-dot")) {
+            char *split = ap_strrchr(path, '.');
+            if (split) {
+                char *slash = ap_strchr(split, '/');
+                if (slash) {
+                    r->path_info = apr_pstrdup(r->pool, slash);
+                    ap_unescape_url_keep2f(r->path_info, 0);
+                    *slash = '\0'; /* truncate path */
+                }
+            }
+        }
+        else {
+            /* before proxy-fcgi-pathinfo had multi-values. This requires the
+             * the FCGI server to fixup PATH_INFO because it's the entire path
+             */
+            r->path_info = apr_pstrcat(r->pool, "/", path, NULL);
+            if (!strcasecmp(pathinfo_type, "unescape")) {
+                ap_unescape_url_keep2f(r->path_info, 0);
+            }
+            ap_log_rerror(APLOG_MARK, APLOG_DEBUG, 0, r, APLOGNO(01061)
+                    "set r->path_info to %s", r->path_info);
+        }
     }
 
     return OK;
@@ -205,6 +252,14 @@
     apr_size_t avail_len, len, required_len;
     int next_elem, starting_elem;
 
+    fcgi_req_config_t *rconf = ap_get_module_config(r->request_config, &proxy_fcgi_module);
+
+    if (rconf) {
+       if (rconf->need_dirwalk) {
+          ap_directory_walk(r);
+       }
+    }
+
     ap_add_common_vars(r);
     ap_add_cgi_vars(r);
 

EOF
fi

apxs -c mod_proxy_fcgi.c
dpkg-divert --divert /usr/lib/apache2/modules/mod_proxy_fcgi.so-DIST --rename /usr/lib/apache2/modules/mod_proxy_fcgi.so
apxs -i mod_proxy_fcgi.la
cd /tmp
apt-src --quiet remove apache2
rm -fR ${SRC_DIR}
