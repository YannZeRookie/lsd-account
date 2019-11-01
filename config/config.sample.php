<?php

// Development?
$development = true;

// Development: If you want to be logged in without having to ask the Bot for a key,
// or if you need to test a specific user, fill-in this variable:
//$connect_force_user = USER_ID_HERE;


// Slim
$slim_debug = $development;

// Twig
$twig_debug = $development;
$twig_auto_reload = true;

// Database
$db_host = 'ENTER DB HOST HERE';
$db_port = 3306;
$db_database = 'ENTER DB NAME HERE';
$db_user = 'ENTER DB USER HERE';
$db_pass = 'ENTER DB PASSWORD HERE';

// Discord API
$discord_api = 'https://discordapp.com/api';
$discord_bot_token = 'BOT TOKEN GOES HERE';
$discord_guild_id = 'YOUR GUILD ID';

// Enable up-synchro of Roles to Discord when updating a user
$discord_upsynch = false;

// Channel to notify when a candidate has to be reviewed
$discord_channel_review = 'CHANNEL ID';


// PayPal
$paypal_url = 'https://www.paypal.com';
$paypal_btn_standard = 'STANDARD BUY BUTTON';
$paypal_btn_custom = 'CUSTOM BUY BUTTON';

// Bot info
$bot_folder = '/path/to/the/bot';

// Admin Update pid file. Contains the pid of the update script
$update_pid = '/tmp/lsd-update.pid';
