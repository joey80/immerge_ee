<?php
/**
 * BACKUP+
 *
 * @author: Benjamin Shirkey
 * @company: Immerge Technologies
 * @website: https://www.immergetech.com
 * @created: 6/28/2018
 * @lastmod: 8/22/2018
 *
 * Backup+ is a small script to provide a backup of a database and website for you to download. You will need to change
 * the database and/or web site directories for each installation.
 *
 * INSTALLATION
 * (1) Configure the variables below for your website.
 * (2) Upload to the root web directory of your website.
 * (3) Run the script from your web browser.
 * (4) Download the files from the backup directory (which will be located below the web root directory).
 */
@ini_set('display_errors', 1);
# ----------------------------------------------------------------------------------------------------------------------
# VARIABLES: Please edit the following credentials for the MySQL database you wish to backup
# ----------------------------------------------------------------------------------------------------------------------

# WEBSITE NAME FOR ARCHIVE FILE
# Suggested to use the FQDN: example.com or www.example.com
$WEBSITE = 'www.timberlakesmith.com';

# DATABASE CONFIGURATION
$DB_HOSTNAME = 'mariadb-162.wc2.phx1.stabletransit.com';
$DB_DATABASE = '459491_tsmith';
$DB_USERNAME = '459491_tsmithu';
$DB_PASSWORD = '56EjadrEtret';

function mySqlConnectionTest($host, $db, $user, $pass) {
	try {
		$conn = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4",
			$user,
			$pass,
			[
				PDO::ATTR_EMULATE_PREPARES => false,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::MYSQL_ATTR_FOUND_ROWS => true
			]
		);
		return true;
	} catch(Exception $ex) {
		return false;
	}
}

# ----------------------------------------------------------------------------------------------------------------------
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<html lang="en">
<head>
    <title>Backup+</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    <style>
        *, *::after, *::before {
            box-sizing: border-box;
        }
        body {
            padding: 1.5rem;
            background: #f2f2f2;
            font-family: Roboto, "Helvetica Neue", sans-serif;
            font-size: 1.5rem;
            line-height: 1.5rem;
        }
        p, span {
            font-size: 1rem;
        }
        .container {
            max-width: 780px;
            margin: 1.5rem auto;
            padding: 1.5rem 2rem;
            background: #fff;
            color: #444;
            border-radius: .25rem;
            box-shadow: 0 .25rem .5rem 0 rgba(0,0,0,.11);
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        input, select, textarea {
            padding: .5rem .75rem;
            border: solid thin #ccc;
            border-radius: .25rem;
            margin-bottom: 1rem;
            width: 100%;
            max-width: 333px;
        }
        button {
            display: inline-block;
            font-size: 1rem;
            line-height: 1rem;
            padding: .75rem 1rem;
            border-radius: .25rem;
            border: none;
            box-shadow: 0 .25rem .5rem 0 rgba(0,0,0,.11);
            background: #4863a0;
            color: #fff;
            font-weight: bold;
            min-width: 111px;
            cursor: pointer;
            text-decoration: none;;
        }
		.warning {
			color: #ff0000;
		}
		.success {
			color: #4cc417;
		}
    </style>
</head>
<body>

<div class="container">

    <div class="text-center">

        <h4>Backup+</h4>

        <?php if(isset($_POST['submit'])): ?>
            <?php

            set_time_limit(0);
            $DATE = date('mdYGis');

            # Get the web root of current site and move to the directory below.
            $WEB_ROOT = $_SERVER['DOCUMENT_ROOT'];
            chdir($WEB_ROOT);
            chdir("../");

            # Get the current working directory.
            $CURRENT_DIR = getcwd();

            # MYSQL PROCESS
            shell_exec("mysqldump -h {$DB_HOSTNAME} -u {$DB_USERNAME} -p{$DB_PASSWORD} {$DB_DATABASE} > {$CURRENT_DIR}/backups/{$DB_DATABASE}-{$DATE}.sql && /dev/null 2>&1 &");

            # ARCHIVE PROCESS
            # Execute the backup command and send the output to null.
            $archiveCommand = "zip -9pr \"{$CURRENT_DIR}/backups/{$WEBSITE}-{$DATE}.zip\" \"{$CURRENT_DIR}\" -x \"{$CURRENT_DIR}/backups/*\" > /dev/null 2>&1 &";

            shell_exec($archiveCommand);

            # Go to sleep little one...
            sleep(1);
			
            ?>

            <h5>Commands Executed</h5>

            <p class="text-left">
                Depending on the website and database sizes it may take a few moments to a few minutes before the files have been created. Please check the backup directory and download the files.
            </p>

            <h5>Run again?</h5>

            <form method="post">
                <button type="submit" name="submit"><i class="fas fa-cogs"></i> Run Backup</button>
            </form>

        <?php else: ?>

			<?php if(mySqlConnectionTest($DB_HOSTNAME, $DB_DATABASE, $DB_USERNAME, $DB_PASSWORD)): ?>
				
				<p>
					<strong class="success">Connection to the database <?= $DB_DATABASE ?> was successful.</strong> 
				</p>

				<?php
					$backupDir = $_SERVER['DOCUMENT_ROOT'].'/../backups';

					if(!file_exists($backupDir))
						@mkdir($backupDir);
				?>

				<?php if(!file_exists($backupDir)): ?>

					<p>
						<strong class="warning">The backup directory could not be created.
					</p>

				<?php else: ?>
					
					<p>
						Click the button below to begin the backup process.
					</p>

					<form method="post">
						<button type="submit" name="submit"><i class="fas fa-cogs"></i> Run Backup</button>
					</form>

				<?php endif; ?>
			
			<?php else: ?>
			
				<p>
					<strong class="warning">Connection to the database <?= $DB_DATABASE ?> failed.</strong> 
				</p>
				<p>
					Please ensure you have edited this script to provide the credentials for MySQL.
				</p>
			
			<?php endif; ?>

        <?php endif; ?>

    </div>
</div>
</body>
</html>