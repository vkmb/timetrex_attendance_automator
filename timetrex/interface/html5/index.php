<?php
/*********************************************************************************
 * TimeTrex is a Workforce Management program developed by
 * TimeTrex Software Inc. Copyright (C) 2003 - 2017 TimeTrex Software Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by
 * the Free Software Foundation with the addition of the following permission
 * added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
 * WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
 * #292 West Kelowna, BC V4T 2E9, Canada or at email address info@timetrex.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Powered by TimeTrex" logo. If the display of the logo is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Powered by TimeTrex".
 ********************************************************************************/

if ( isset($_GET['disable_db']) AND $_GET['disable_db'] == 1 ) {
	$disable_database_connection = TRUE;
}

require_once('../../includes/global.inc.php');
if ( isset( $_SERVER['REQUEST_URI'] ) AND strpos( $_SERVER['REQUEST_URI'], '//' ) !== FALSE ) { //Always strip duplicate a slashes from URL whenever possible.
	Debug::text('Stripping duplicate slashes from URL: '. $_SERVER['REQUEST_URI'], __FILE__, __LINE__, __METHOD__, 10);
	Redirect::Page( Environment::stripDuplicateSlashes( $_SERVER['REQUEST_URI'] ) );
}

forceNoCacheHeaders(); //Send headers to disable caching.

//Break out of any domain masking that may exist for security reasons.
Misc::checkValidDomain();

//Skip this step if disable_database_connection is enabled or the user is going through the installer still
$system_settings = array();
$primary_company = FALSE;
$clf = new CompanyListFactory();
if ( ( !isset($disable_database_connection) OR ( isset($disable_database_connection) AND $disable_database_connection != TRUE ) )
	AND ( !isset($config_vars['other']['installer_enabled']) OR ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] != TRUE ) )) {
	//Get all system settings, so they can be used even if the user isn't logged in, such as the login page.
	try {
		$sslf = new SystemSettingListFactory();
		$system_settings = $sslf->getAllArray();
		unset($sslf);

		//Get primary company data needs to be used when user isn't logged in as well.
		$clf->getByID( PRIMARY_COMPANY_ID );
		if ( $clf->getRecordCount() == 1 ) {
			$primary_company = $clf->getCurrent();
		}
	} catch (Exception $e) {
		//Database not initialized, or some error, redirect to Install page.
		throw new DBError($e, 'DBInitialize');
	}
}

if ( DEPLOYMENT_ON_DEMAND == FALSE AND isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == TRUE AND !isset($_GET['installer']) ) {
	//Installer is enabled, check to see if any companies have been created, if not redirect to installer automatically, as they skipped it somehow.
	//Check if Company table exists first, incase the installer hasn't run at all, this avoids a SQL error.
	$installer_url = 'index.php?installer=1&disable_db=1&external_installer=1#!m=Install&a=license&external_installer=0';
	if ( isset($db) ) {
		$install_obj = new Install();
		$install_obj->setDatabaseConnection( $db );
		if ( $install_obj->checkTableExists('company') == TRUE ) {
			$clf = TTnew( 'CompanyListFactory' );
			$clf->getAll();
			if ( $clf->getRecordCount() == 0 ) {
				Redirect::Page( URLBuilder::getURL( NULL, $installer_url ) );
			}
		} else {
			Redirect::Page( URLBuilder::getURL( NULL, $installer_url ) );
		}
	} else {
		Redirect::Page( URLBuilder::getURL( NULL, $installer_url ) );
	}
	unset($install_obj, $clf, $installer_url);
}
Misc::redirectMobileBrowser(); //Redirect mobile browsers automatically.
Misc::redirectUnSupportedBrowser(); //Redirect unsupported web browsers automatically.

//Handle HTTPAuthentication after all redirects may have finished.
$authentication = new Authentication();
if ( $authentication->getHTTPAuthenticationUsername() == FALSE ) {
	$authentication->HTTPAuthenticationHeader();
} else {
	if ( $authentication->loginHTTPAuthentication() == FALSE ) {
		$authentication->HTTPAuthenticationHeader();
	}
}
unset($authentication);
?>
	<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<meta name="Keywords" content="workforce management, time and attendance, payroll software, online timesheet software, open source payroll, online employee scheduling software, employee time clock software, online job costing software, workforce management, flexible scheduling solutions, easy scheduling solutions, track employee attendance, monitor employee attendance, employee time clock, employee scheduling, true real-time time sheets, accruals and time banks, payroll system, time management system" />
			<meta name="Description" content="Workforce Management Software for tracking employee time and attendance, employee time clock software, employee scheduling software and payroll software all in a single package. Also calculate complex over time and premium time business policies and can identify labor costs attributed to branches and departments. Managers can now track and monitor their workforce easily." />
			<script src="global/Debug.js?v=<?php echo APPLICATION_BUILD?>"></script>
			<title><?php echo APPLICATION_NAME .' Workforce Management';?></title>
			<link rel="shortcut icon" type="image/ico" href="<?php echo Environment::getBaseURL();?>../favicon.ico">
			<script src="global/RateLimit.js?v=<?php echo APPLICATION_BUILD?>"></script>
			<?php if ( file_exists('theme/default/css/login.composite.css') ) { //See tools/compile/Gruntfile.js to configure which files are included in the composites... ?>
				<link rel="stylesheet" type="text/css" href="theme/default/css/login.composite.css?v=<?php echo APPLICATION_BUILD?>">
				<script>
					use_composite_css_files = true;
				</script>
			<?php } else { ?>
				<link rel="stylesheet" type="text/css" href="theme/default/css/application.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/jquery-ui/jquery-ui.custom.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/ui.jqgrid.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/views/login/LoginView.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/global/widgets/ribbon/RibbonView.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/global/widgets/search_panel/SearchPanel.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/views/attendance/timesheet/TimeSheetView.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/views/attendance/schedule/ScheduleView.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/global/widgets/timepicker/TTimePicker.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/global/widgets/datepicker/TDatePicker.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/right_click_menu/rightclickmenu.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/views/wizard/Wizard.css?v=<?php echo APPLICATION_BUILD?>">
				<link rel="stylesheet" type="text/css" href="theme/default/css/image_area_select/imgareaselect-default.css?v=<?php echo APPLICATION_BUILD?>">
				<?php if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) { ?>
					<link rel="stylesheet" type="text/css" href="framework/leaflet/leaflet.css?v=<?php echo APPLICATION_BUILD?>">
					<link rel="stylesheet" type="text/css" href="framework/leaflet/leaflet-draw/leaflet.draw.css?v=<?php echo APPLICATION_BUILD?>">
					<link rel="stylesheet" type="text/css" href="framework/leaflet/leaflet-routing-machine/leaflet-routing-machine.css?v=<?php echo APPLICATION_BUILD?>">
				<?php } ?>
			<link rel="stylesheet" type="text/css" href="theme/default/css/text_layer_builder.css?v=<?php echo APPLICATION_BUILD?>">
				<script>
					use_composite_css_files = false;
				</script>
			<?php } ?>

			<?php if ( file_exists('login.composite.js') ) { //See tools/compile/Gruntfile.js to configure which files are included in the composites... ?>
				<script src="global/CookieSetting.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="global/APIGlobal.js.php?v=<?php echo APPLICATION_BUILD?><?php if ( isset($disable_database_connection) AND $disable_database_connection == TRUE ) { echo '&disable_db=1'; }?>"></script>
				<script src="login.composite.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<!-- <script async src="base.composite.js?v=<?php echo APPLICATION_BUILD?>"></script> -->
				<script>
					use_composite_js_files = true;
					//Global.addCss( "universe.composite.css" );
				</script>
			<?php } else { ?>
				<script src="global/CookieSetting.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="global/APIGlobal.js.php?v=<?php echo APPLICATION_BUILD?><?php if ( isset($disable_database_connection) AND $disable_database_connection == TRUE ) { echo '&disable_db=1'; }?>"></script>
				<script src="framework/jquery.min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/jquery.form.min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/backbone/underscore-min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/backbone/backbone-min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/jquery.masonry.min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/interact.min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/tinymce/tinymce.min.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="global/Global.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="global/LocalCacheData.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script src="framework/widgets/color-picker/color-picker.js?v=<?php echo APPLICATION_BUILD?>"></script>
				<script>
					use_composite_js_files = false;
				</script>
			<?php } ?>
		</head>
	<?php
	/*
	<!--z-index
	Alert: 6001 need larger than validation
	DatePicker:100
	Awesomebox: 100
	Progressbar: 100
	ribbon sub menu: 100
	right click menu: 100
	validation: 6000 set by plugin
	color-picker: 999

	Wizard: 50
	camera shooter in wizard 51

	EditView : 40
	Bottom minimize tab: 39

	Login view:10
	-->
	*/
	?>
	<body class="login-bg" oncontextmenu="return true;">


	<div class="need-hidden-element" ><a href="https://www.timetrex.com">Workforce Management Software</a><a href="https://www.timetrex.com/time_and_attendance.php">Time and Attendance</a></div>
	<div id="topContainer" class="top-container"></div>
	<div id="contentContainer" class="content-container">
		<div class="loading-view">
			<!--[if (gt IE 8)|!(IE)]><!-->
			<div class="progress-bar-div">
				<progress class="progress-bar" max="100" value="10">
					<strong>Progress: 100% Complete.</strong>
				</progress>
				<span class="progress-label">Initializing...</span>
			</div>
			<!--<![endif]-->
		</div>
	</div>
	<div class="need-hidden-element"><a href="https://www.timetrex.com/download.php">Download Time and Attendance Software</a></div>
	<div id="bottomContainer" class="bottom-container" ondragstart="return false;">
		<ul class="signal-strength">
			<li class="signal-strength-very-weak"><div></div></li>
			<li class="signal-strength-weak"><div></div></li>
			<li class="signal-strength-strong"><div></div></li>
			<li class="signal-strength-pretty-strong"><div></div></li>
		</ul>
		<div class="copyright-container">
			<a id="copy_right_logo_link" class="copy-right-logo-link" target="_blank"><img id="copy_right_logo" class="copy-right-logo"></a>
			<a id="copy_right_info" class="copy-right-info" target="_blank" style="display: none"></a>
			<span id="copy_right_info_1" class="copy-right-info" style="display: none">&nbsp;&nbsp;<?php /*REMOVING OR CHANGING THIS COPYRIGHT NOTICE IS IN STRICT VIOLATION OF THE LICENSE AND COPYRIGHT AGREEMENT*/ echo COPYRIGHT_NOTICE;?></span>
		</div>
		<div id="feedbackContainer" class="feedback-container">
			<span>Overall, how are you feeling about <?php echo APPLICATION_NAME; ?>?</span>
			<img class="filter yay-filter" title="Yay!" data-feedback = 1 alt="happy"  >
			<img class="filter meh-filter" title="Meh." data-feedback = 0 alt="neutral" >
			<img class="filter grr-filter" title="Grr!" data-feedback = -1 alt="sad" >
		</div>
	</div>

	<div id="overlay" class=""></div>

	</body>

	<iframe style="display: none" id="hideReportIFrame" name="hideReportIFrame"></iframe>
	<script>
		//Hide elements that show hidden link for search friendly
		hideElements();

		//Don't not show loading bar if refresh
		if ( Global.isSet( LocalCacheData.getLoginUser() ) ) {
			$( ".loading-view" ).hide();
		} else {
			setProgress()
		}

		function setProgress() {
			loading_bar_time = setInterval( function() {
				var progress_bar = $( ".progress-bar" );
				var c_value = progress_bar.attr( "value" );

				if ( c_value < 90 ) {
					progress_bar.attr( "value", c_value + 10 );
				}
			}, 1000 );
		}

		function cleanProgress() {
			if ( $( ".loading-view" ).is( ":visible" ) ) {

				var progress_bar = $( ".progress-bar" );
				progress_bar.attr( "value", 100 );
				clearInterval( loading_bar_time );

				loading_bar_time = setInterval( function() {
					$( ".progress-bar-div" ).hide();
					clearInterval( loading_bar_time );
				}, 50 );
			}
		}

		function hideElements(){
			var elements = document.getElementsByClassName( 'need-hidden-element' );

			for ( var i = 0; i < elements.length; i++ ) {
				elements[i].style.display = 'none';
			}
		}
	</script>

	<script src="framework/require.js?v=<?php echo APPLICATION_BUILD?>" data-main="main.js?v=<?php echo APPLICATION_BUILD?>"></script>
	<!-- <?php echo Misc::getInstanceIdentificationString( $primary_company, $system_settings );?>  -->
	</html>
<?php
Debug::writeToLog();
?>