<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'article-automation' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'x&PO#%wFRUy.GT@|N-zc=?Cz<WEj?GE^FFD#&`Kka6 r?Y[yqP~kM)$Y}AP3T}o(' );
define( 'SECURE_AUTH_KEY',  '35U18SVf.IoIO?Z2Q}~e(B o^B ,ttMg0gL-M;8OA_(hBkWl3n]^YjPi?ZR87a?;' );
define( 'LOGGED_IN_KEY',    ';.<|a6~ZZZ&+0;u-kFy-CX3#T}qX/8K7bzKV=jE)hd)|G?_ue]7ph4u_xhhGGcg6' );
define( 'NONCE_KEY',        'k3|8&Xc[)Wg`p7#u_Qxnq/=`|%?PbhHFFqeBZ_tuvL$h1ETDGxegt+!3*c1f6Vk;' );
define( 'AUTH_SALT',        'IV~h&8zdI{joKt}jhUTEqdw 4RaI%-wHn,#h3I(5hf|M]W!t~p{DTVMJjoYkJK3O' );
define( 'SECURE_AUTH_SALT', 'SVE+O< k8=XhG{~M^>k?<Z++l~ojD*^d3I/YQ?sOl[[K>>@%OFXYD@w[J5IWi^8;' );
define( 'LOGGED_IN_SALT',   'u]t*N,P&.F7.bV7{b=eZ+~.L=)aC&H?^$Dd&1J>^xist!ovA,bAUuqo M,u,8Dr-' );
define( 'NONCE_SALT',       '@d<>ABj42$Csfo2NXu6}&}4F]!(u(eW<rZd<cD63+u@twF$q:$ER7RDE1;zi 0gd' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
