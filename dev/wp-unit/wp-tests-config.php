<?php
/**
 * @version 1.6.1
 *
 */

// Point to local memcache servers (Requirement of sites like WPE).
$GLOBALS['memcached_servers'] = [ '127.0.0.1:11211' ];

if ( file_exists( __DIR__ . '/local-config.php' ) ) {
	include __DIR__ . '/local-config.php';
}

$root = dirname( __DIR__, 2 );

define( 'WP_SITE_ROOT', $root . DIRECTORY_SEPARATOR );

$config_defaults = [
	'ABSPATH'                   => $root . '/wp/',
	'BLOG_ID_CURRENT_SITE'      => 1,
	'BOOTSTRAP'                 => getenv( 'BOOTSTRAP' ),
	'DB_HOST'                   => 'localhost',
	'DB_CHARSET'                => 'utf8mb4',
	'DB_COLLATE'                => '',
	'DB_NAME'                   => getenv( 'DB_NAME' ),
	'DB_PASSWORD'               => getenv( 'DB_PASSWORD' ),
	'DB_USER'                   => getenv( 'DB_USER' ),
	'DOMAIN_CURRENT_SITE'       => getenv( 'HTTP_HOST' ),
	'FORCE_SSL_ADMIN'           => false,
	'FORCE_SSL_LOGIN'           => false,
	'PATH_CURRENT_SITE'         => '/',
	'SAVEQUERIES'               => true,
	'SCRIPT_DEBUG'              => false,
	'SEND_MAIL'                 => false,
	'SITE_ID_CURRENT_SITE'      => 1,
	'WC_USE_TRANSACTIONS'       => false,
	'WP_CACHE_DIR'              => $root . '/wp-content/cache',
	'WP_CONTENT_DIR'            => $root . '/wp-content',
	'WP_CONTENT_URL'            => 'http://' . getenv( 'HTTP_HOST' ) . '/wp-content',
	'WP_DEBUG'                  => true,
	'WP_DEFAULT_THEME'          => 'core',
	'WP_ENVIRONMENT_TYPE'       => 'local',
	'WP_TESTS_CONFIG_FILE_PATH' => $root . '/dev/wp-unit/wp-tests-config.php',
	'WP_TESTS_DIR'              => $root,
	'WP_TESTS_EMAIL'            => 'support@onpointplugins.com',
	'WP_TESTS_SNAPSHOTS_BASE'   => 'Lipe\Project',
	'WP_TESTS_SNAPSHOTS_DIR'    => __DIR__ . '/__snapshots__',
	'WP_TESTS_TITLE'            => 'Starting Point',
	'WP_PHP_BINARY'             => 'php',
	'WP_TESTS_DOMAIN'           => getenv( 'HTTP_HOST' ),
	'WP_TESTS_MULTISITE'        => getenv( 'WP_TESTS_MULTISITE' ),
	'WP_TESTS_SEND_MAIL'        => false,
	'WP_TESTS_TABLE_PREFIX'     => 'sp_',
	'WP_UNIT_DIR'               => getenv( 'WP_UNIT_DIR' ),

	// Security Hashes
	'AUTH_KEY'                  => 'hXFhaMI|7Ao7?fo0A_Nov|K9d7P:D2|)GcW&yfDqG5-<Q|W!l_H4-.sPqJumwE5*',
	'SECURE_AUTH_KEY'           => 'K3`2L&8g8]dWWv?FONg3*=3bI5}dA#NG786VMIP}+:uZrII!w81la[UqnH#>A&|V',
	'LOGGED_IN_KEY'             => 'B+DTKr[a=&Dxy;.PeOo}m*_mZ]ATQ06xJ-Eu#E7sGO1LJd3Y5Tar<pr7i#1/+b/l',
	'NONCE_KEY'                 => 'VEfK ma_fy-q[h6q-F<=,TJRp,%QCUEe`gm[%@% A6F#_0-Na!zqW9*ft x0soX#',
	'AUTH_SALT'                 => 'AKzr K#m%A{+0D_CD`y_Xb`!@-]r}fb+Di$+,f-5~%:5}n0;?yR)(nAuAQ?|w66a',
	'SECURE_AUTH_SALT'          => '0RYzT6674_V`qmnxOJW*F{XL!}l^oVKb(+i}5a(b|-?;L<-~tY7f?+D-Ax-PK)0;',
	'LOGGED_IN_SALT'            => ']MW-%45v7&b6;E9^ks]>r0th7`N;X3_k<vtYrlu%QrujI-R yu,X-d=iZ YVI8#R',
	'NONCE_SALT'                => 'l482|1s< F/y|1a,C8RQy2hR%5<<@5[7XS^X|iA`8@fe`SBG@fSC%LOIm *?8Li#',
];

foreach ( $config_defaults as $config_default_key => $config_default_value ) {
	if ( ! defined( $config_default_key ) ) {
		define( $config_default_key, $config_default_value );
	}
}
