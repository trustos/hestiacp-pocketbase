server {
    listen %ip%:%proxy_port%;
    server_name %domain_idn% %alias_idn%;
    error_log /var/log/%web_system%/domains/%domain%.error.log error;

    include %home%/%user%/conf/web/%domain%/nginx.conf_*;

    include %home%/%user%/hestiacp_pocketbase_config/web/%domain%/pocketbase-app.conf;

    location /error/ {
        alias %home%/%user%/web/%domain%/document_errors/;
    }

    location ~ /\.ht {return 404;}
    location ~ /\.svn/ {return 404;}
    location ~ /\.git/ {return 404;}
    location ~ /\.hg/ {return 404;}
    location ~ /\.bzr/ {return 404;}
}
