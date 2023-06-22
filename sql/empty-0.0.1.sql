DROP TABLE IF EXISTS `glpi_plugin_ipam_addressings`;
CREATE TABLE `glpi_plugin_ipam_addressings` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `use_as_filter` tinyint NOT NULL default '0',
   `networks_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_networks (id)',
   `locations_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
   `fqdns_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_fqdns (id)',
   `vlans_id` int unsigned NOT NULL DEFAULT '0'COMMENT 'RELATION to glpi_vlans (id)',
   `cidr` int unsigned NOT NULL default '0',
   `begin_ip` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `end_ip` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `alloted_ip` tinyint NOT NULL default '0',
   `double_ip` tinyint NOT NULL default '0',
   `free_ip` tinyint NOT NULL default '0',
   `reserved_ip` tinyint NOT NULL default '0',
   `comment` text collate utf8mb4_unicode_ci,
   `is_deleted` tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`),
   KEY `entities_id` (`entities_id`),
   KEY `networks_id` (`networks_id`),
   KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ipam_configs`;
CREATE TABLE `glpi_plugin_ipam_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `alloted_ip` tinyint NOT NULL default '0',
   `double_ip` tinyint NOT NULL default '0',
   `free_ip` tinyint NOT NULL default '0',
   `reserved_ip` tinyint NOT NULL default '0',
   `used_system` tinyint NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ipam_filters`;
CREATE TABLE `glpi_plugin_ipam_filters` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `plugin_ipam_addressings_id` int unsigned NOT NULL default '0',
   `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `begin_ip` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `end_ip` varchar(255) collate utf8mb4_unicode_ci default NULL,
   `type` varchar(255) collate utf8mb4_unicode_ci default NULL,
   PRIMARY KEY  (`id`),
   KEY `entities_id` (`entities_id`),
   KEY `plugin_ipam_addressings_id` (`plugin_ipam_addressings_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_ipam_ipcomments`
(
    `id`                               int unsigned NOT NULL auto_increment,
    `plugin_ipam_addressings_id` int unsigned NOT NULL default '0',
    `ipname`                           varchar(255) collate utf8mb4_unicode_ci default NULL,
    `comments`                         LONGTEXT collate utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY  `plugin_ipam_addressings_id` (`plugin_ipam_addressings_id`),
    KEY  `ipname` (`ipname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_plugin_ipam_configs` VALUES ('1','1','1','1','1','0');

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpamAddressing',2,2,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpamAddressing',3,6,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpamAddressing',4,5,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpamAddressing',1000,3,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpamAddressing',1001,4,0);
