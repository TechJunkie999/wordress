<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', 'mines003' );

/** MySQL hostname */
define( 'DB_HOST', 'hamzakhan2695.ccjr8wu9wquv.us-east-1.rds.amazonaws.com' );

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
define( 'AUTH_KEY',         'K4%r.S&]+f}AN?25`Y_xE>uA# R]iaU,odKqB pA3H?ieaBq)zPz7<ETnw,L=v]h' );
define( 'SECURE_AUTH_KEY',  '2R:t^]2&*a@l#2J.K*IyyKCTQe2DgBp.[r&#ToX1+zj?V[MS*cbt<CXEhfUik/0q' );
define( 'LOGGED_IN_KEY',    '3dAb4[WW18e%[^ IE4*=?=Zh 6b4^8yq @J%P}m~KA=cY7_6mBUi&JV]5u#?dSVy' );
define( 'NONCE_KEY',        '[h /xF>3GOvPscBtjS[|Czd~s/l@Y*Iq4&Ntw25GSL].zl{b_$/9X0pvQu:4BF`k' );
define( 'AUTH_SALT',        't7Lq/T&^oTG^qw{ ^=:x@d;%_~kprwnD(kFt:%&Pc~_u}6w}p8!Hc_CUWX/RoUS.' );
define( 'SECURE_AUTH_SALT', 'ia#9X_nz9eR$/V{X7B_y]!FWxY|E} ?Kku$n|YU1t+OlHW!-3%|eV0qF)0gS(# @' );
define( 'LOGGED_IN_SALT',   '}PZw%Ou3]%Hf]l@eN813X`Z?=u|{3[v>1BczqSq+75OSH4Em*;V{_OfJwg{tfk;t' );
define( 'NONCE_SALT',       'H#^F_I:`I<5Ahg;)l^$V9#Uoh=bT2PqHVE?-K[J4>M)X}%{a>MMW3V~CHVA{qEe7' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
