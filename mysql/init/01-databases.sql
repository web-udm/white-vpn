CREATE DATABASE IF NOT EXISTS whitevpn_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS whitevpn_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON whitevpn_dev.* TO 'whitevpn'@'%';
GRANT ALL PRIVILEGES ON whitevpn_prod.* TO 'whitevpn'@'%';
