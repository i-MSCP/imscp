location = /pma {
    return 301 /pma/;
}

location ^~ /pma/ {
    root {GUI_PUBLIC_DIR}/tools;

    location ~ \.php$ {
        include imscp_fastcgi.conf;
    }
}
