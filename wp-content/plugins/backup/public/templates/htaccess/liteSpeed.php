# LITESPEED START
<IfModule Litespeed>
    RewriteEngine On
    RewriteRule .* - [E=noabort:1]
</IfModule>
# LITESPEED END
