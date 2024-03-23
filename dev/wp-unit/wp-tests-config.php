<?php
/**
 * @version 1.6.2
 *
 */

// Point to local memcache servers (Requirement of sites like WPE).
$GLOBALS['memcached_servers'] = [ '127.0.0.1:11211' ];

$root = dirname( __DIR__, 2 );

if ( file_exists( __DIR__ . '/local-config.php' ) ) {
	require __DIR__ . '/local-config.php';
} elseif ( file_exists( __DIR__ . '/default-local-config.php' ) ) {
	require __DIR__ . '/default-local-config.php';
}

$config_defaults = [
	'ABSPATH'                   => $root . '/wp/',
	'BLOG_ID_CURRENT_SITE'      => 1,
	'BOOTSTRAP'                 => getenv( 'BOOTSTRAP' ),
	'DB_CHARSET'                => 'utf8mb4',
	'DB_COLLATE'                => '',
	'DB_HOST'                   => 'localhost',
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
	'WP_PHP_BINARY'             => 'php',
	'WP_SITE_ROOT'              => $root . DIRECTORY_SEPARATOR,
	'WP_TESTS_CONFIG_FILE_PATH' => $root . '/dev/wp-unit/wp-tests-config.php',
	'WP_TESTS_DIR'              => $root,
	'WP_TESTS_DOMAIN'           => getenv( 'HTTP_HOST' ),
	'WP_TESTS_EMAIL'            => 'unit-tests@onpointplugins.com',
	'WP_TESTS_MULTISITE'        => getenv( 'WP_TESTS_MULTISITE' ),
	'WP_TESTS_SEND_MAIL'        => false,
	'WP_TESTS_SNAPSHOTS_BASE'   => 'Lipe\Project',
	'WP_TESTS_SNAPSHOTS_DIR'    => __DIR__ . '/__snapshots__',
	'WP_TESTS_TABLE_PREFIX'     => 'sp_',
	'WP_TESTS_TITLE'            => 'Starting Point',
	'WP_UNIT_DIR'               => getenv( 'WP_UNIT_DIR' ),

	// @link https://api.wordpress.org/secret-key/1.1/salt/
	'AUTH_KEY'                  => '=>PIMlBM]llW|t()Pe:2q(X;r~hzz#3@E:z{nK:wJVx=U?4qt-wG.y.j^ JOS<B8N',
	'SECURE_AUTH_KEY'           => '-BZosq5i=^fH9+H-/]Qn { odsV2)kfx)n]n+NAa$8YYh<_gx}`r++n~~hI3B;w=',
	'LOGGED_IN_KEY'             => '|k]> RuP}wnwO$OWmDA:++;#BgXA)$k!cH+:AIxuK=>L{?NZ44 B0z$6o]_l=>h=>E#',
	'NONCE_KEY'                 => 'zh+k-gZ0HNGs%nnw-87f/5dF_FQdAli!E9ty XUqr+|&:xrq|@sf c7Tlr;l!HWs',
	'AUTH_SALT'                 => '0|Io@R=>E1iEga.;=>Sx]G$=>_Ya/TzZj@+uO+OL]u7N^ub[R_dX28f@aZ`Jq[{7BQ~',
	'SECURE_AUTH_SALT'          => 'vG$yOP8&%&i^U-COwiM-)Gc7[6jo]*=>xz@*0d<6u[I:+)`a8dH?P-9[rR343mhB8',
	'LOGGED_IN_SALT'            => '|W)c+14D:>=q-YEfa(S))0w&8rI[?pA~fkibW5U=>?24-|}o?Dnz/<wh?HJ+QX:}G',
	'NONCE_SALT'                => '6aB[7.`?GYu0}03#qmF:[~v#rSN%Y(I=>r[i7}1rxNA|EURV?AuTLkrsUE?FekJ..',
];

foreach ( $config_defaults as $config_default_key => $config_default_value ) {
	if ( ! defined( $config_default_key ) ) {
		define( $config_default_key, $config_default_value );
	}
}
