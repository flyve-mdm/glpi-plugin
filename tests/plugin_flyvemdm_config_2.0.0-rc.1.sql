-- configuration of Flyve MDM in a GLPI instance

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_broker_address', '192.168.0.9');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_broker_internal_address', '127.0.0.1');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_broker_port', '1883');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_broker_tls_port', '8883');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_tls_for_clients', '1');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_tls_for_backend', '1');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_use_client_cert', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_broker_tls_ciphers', 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_user', 'flyvemdm-backend');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'mqtt_passwd', 'VzSXR1SpSgq7GUV1OvmPhFv2cTuY4wLo');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'instance_id', 'B68KAoGre3ZZydxmJsNK3S3Dckx7W3/Uq8IusA6BzXvhLBaJy6M48ivVxcYUnx8DuHEM9iL626Fy3mCbiDeptg==');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'registered_profiles_id', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'guest_profiles_id', '9');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'agent_profiles_id', '10');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'service_profiles_id', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'debug_enrolment', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'debug_noexpire', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'debug_save_inventory', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'ssl_cert_url', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'default_device_limit', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'default_agent_url', 'https://play.google.com/store/apps/details?id=org.flyve.mdm.agent');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'android_bugcollecctor_url', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'android_bugcollector_login', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'android_bugcollector_passwd', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'webapp_url', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'demo_mode', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'demo_time_limit', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'inactive_registered_profiles_id', '');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'computertypes_id', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'agentusercategories_id', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'invitation_deeplink', 'http://flyve.org/deeplink/');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'show_wizard', '0');
INSERT INTO `glpi_configs` (`id`, `context`, `name`, `value`) VALUES
	(NULL, 'flyvemdm', 'version', '2.0.0-rc.1');

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
