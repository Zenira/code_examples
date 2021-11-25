<?
	/**
	*
	* This script provides an area for users to edit their account information.
	*
	* @author    Lindsay Sauer <redacted@email.com>
	* @version   1.0
	*/
	$pageHeader = "Manage Account";
	$accountManagementPage = true;

	if(false) {
		require_once('../core/template/template_top.php');
		require_once('../core/class/html_table.php');
		require_once('../core/other_includes/states_countries.php');
		require_once('../core/functions/user_settings.php');
	} else {
		if(!isset($GLOBALS['wwwBaseDir'])) require_once('../core/other_includes/set_base_dir.php');
		require_once($GLOBALS['wwwBaseDir'] . 'core/template/template_top.php');
		require_once($GLOBALS['wwwBaseDir'] . 'core/class/html_table.php');
		require_once($GLOBALS['wwwBaseDir'] . 'core/other_includes/states_countries.php');
	}

	verify('', true, '');
?>
<h1 id="pageHeader" class="ui dividing header"><?=$pageHeader?></h1>
<? if ($_SESSION['account_error']['pilot']) { ?>
	<div id="pilotMissingDataError" class="ui message red">
		Your user account has been set to access pilot assessments. These assessments are being taken without a final cut score while information about the assessment is being gathered. Pilot users elect to provide additional demographic data which is used along with statistical analysis of the testing results to determine the proper level for certification.<br><br>

    Please provide all missing data in order to continue to use the site as a pilot user.<br><br>

		If you do not wish to participate in the pilot program, you may choose to opt out by clicking the button below. You will be able to access any assigned credentials which have already passed the pilot program.
	</div>
	<div id="optOut">
		<input type="submit" value="I Do NOT Wish to Provide Pilot Information" name="optOutBtn" id="optOutBtn" class="ui class blue button" />
	</div>
<? } ?>
<style type="text/css">
	#customerInfoForm, #customerPasswordForm { line-height: 2em; }
	#customerInfoForm { padding-bottom: 50px; }
	#customerInfoForm tr td:first-child label, #customerPasswordForm tr td:first-child label { min-width: 80px; }
	#customerInfoForm td, #customerPasswordForm td {vertical-align: top; padding: 0 10px 10px 0;}
	#highest_level_school_completed, #birthday { max-width: 100px; }
	#region { display: none; }
	.sex-field { max-width: 225px; }
	.textInput { margin: 0; float: none;padding: 5px; }
	.edit-section-title { font-size:14px;color:#333; font-weight: bold; }
	.notice, #pilotMissingDataError { max-width: 750px; }
</style>
<?
	require_once($GLOBALS['wwwBaseDir'] . 'core/functions/user_settings.php');

	// Get additional user information
	$qstr = "
			Select *
			From user_private_info
			Where user_id = '".$_SESSION['user']['user_id']."'
			";
	$query = new Query($qstr);
	$privateData = $query->getResults();

	$userIsPilot = $_SESSION['user']['user_pilot'];
	$user_address = stripslashes($privateData[0]['user_address_cs']);
	$user_city = stripslashes($privateData[0]['user_city_cs']);
	$user_state = stripslashes($privateData[0]['user_state_cs']);
	$user_postal_code = stripslashes($privateData[0]['user_postal_code_cs']);
	$user_country = stripslashes($privateData[0]['user_country_cs']);
	$user_birthday = $privateData[0]['user_birthday_cs'];
	$user_highest_level_school_completed = stripslashes($privateData[0]['user_highest_level_school_completed_cs']);
	$user_race = stripslashes($privateData[0]['user_race_cs']);
	$user_sex = $privateData[0]['user_sex_cs'];
	$user_special_needs = stripslashes($privateData[0]['user_special_needs_cs']);

	$stateSelect = getStateSelect();
	$countrySelect = getCountrySelect();
	$disabilityCategories = getDisabilityCategories();
	$races = getRaces();
	$sexes = getSexes();
	$schoolLevels = getSchoolLevels();

	$min_birthday = date("Y", strtotime('-120 years'));
	$max_birthday = date("Y", strtotime('-10 years'));
?>
<script type="text/javascript">
	$(document).ready(function () {
		var user_id = "<?= $_SESSION['user']['user_id'] ?>";
		var currentYear = moment().format("YYYY");

		$('#customerInfoForm .ui.dropdown').dropdown({
			clearable: true
		});

		// Select State and Country
		$('#stateList').dropdown('set selected', "<?=$user_state?>");
		$('#countryList').dropdown('set selected', "<?=$user_country?>");
		adjustStateRegionInput();

		$('.menu .item').tab();

		$('#countryList').change (function() {
			adjustStateRegionInput();
		});

		$('#highest_level_school_completed, #birthday').keyup( function(e) {
			$(this).removeClass('form-error');
			var firstChar = this.value.charAt(0);
			if (firstChar < 1 || firstChar > 2){
				this.value = '';
			}
			if (this.value.length == 4 && (this.value < $(this).attr('min') || this.value > $(this).attr('max'))) {
				$(this).addClass('form-error');
			}
			else if (this.value.length > 4) {
				this.value = this.value.substring(0, 4);
			}
		});

		$('#optOutBtn').click(function() {
			var userPilot = "No";

			$.get("<?=$GLOBALS['wwwBaseDir']?>core/user_management_ajax.php", {
				func: "optOutUserPilot",
				newPilotStatus: userPilot
			}, function(response){
				var modal_settings = {
					id: "pilotOptOutModal",
					size: 'tiny',
					buttons: {
						"Close": { action: function() {
								location.reload(true);
						},
							color: 'black'
						}
					}
				};

				if (response !== "TRUE") {
					modal_settings.content = $.trim(response);
					modal_settings.error = true;
					modal_settings.title = "Error";
				} else {
					modal_settings.content = "You have successfully opted out of becoming a pilot user. You will NOT be able to access pilot assessments.";
					modal_settings.title = "Success";
					$('#pilotMissingDataError').remove();
					$('#optOut').remove();

				}

				modal(modal_settings);

			});
		});

		$('#savePasswordBtn').click(function() {
			var errorMsg = '';
			pass = $.trim($('#pass_1').val());
			pass2 = $.trim($('#pass_2').val());

			if (!validPassword(pass)) {
				errorMsg = "Please enter a stronger password.";
			}
			else if (!validPasswords(pass, pass2)) {
				errorMsg = "Your passwords did not match.";
			}

			if (errorMsg) {
				var modal_settings = {
					id: "errorSaveUserPasswordModal",
					title: "Error",
					content: errorMsg,
					error: true,
					size: 'tiny',
					buttons: {
						"Close": { action: "close"}
					}
				};
				modal(modal_settings);
				return false;
			}

			$.get("<?=$GLOBALS['wwwBaseDir']?>core/user_management_ajax.php", {
				func: "saveUserPassword",
				newPassword: pass
			}, function(response){
				var modal_settings = {
					id: "errorSaveUserPasswordModal",
					size: 'tiny',
					buttons: {
						"Close": { action: "close"}
					}
				};

				if (response !== "TRUE") {
					modal_settings.content = $.trim(response);
					modal_settings.error = true;
					modal_settings.title = "Error";
				} else {
					modal_settings.content = "Password Updated";
					modal_settings.title = "Success";

					// Reset password strength
					$('.passwordField').val(null);

					$('.pass-text').html('');
					$('.pass-colorbar').css('width', '0%');
				}

				modal(modal_settings);
			});
		});

		$('#customerInfoForm').on('click', '#saveInfoBtn', function() {
			$('.form-error').removeClass('form-error');
			updateErrorMsgStatus();
			var dialogBoxTitle = "User Information";
			var errorMsg = '';
			var $first_name = $('#first_name'), user_first_name = $first_name.val();
			var $last_name = $('#last_name'), user_last_name = $last_name.val();
			var $email = $('#email'), user_email = $email.val();
			var $address = $('#address'), address = $address.val();
			var $city = $('#city'), city = $city.val();
			var $state = $('#stateList'), state = $state.val();
			if (state == '') {
				$state = $('#region'), state = $state.val();
			}
			var $postal_code = $('#postal_code'), postal_code = $postal_code.val();
			var $country = $('#countryList'), country = $country.val();
			var $birthday = $('#birthday'), birthday = $birthday.val();
			var $highest_level_school_completed = $('#highest_level_school_completed'), highest_level_school_completed = $highest_level_school_completed.val();
			<? if ($userIsPilot == "Yes") {?>
			var $sex = $('#sex'), sex = $sex.val();
			var $race = $('#race'), race = $race.val();
			var $special_needs = $('#special_needs'), special_needs = $special_needs.val();
			<? } ?>

			var requiredFields = $('#customerInfoForm input[required]:visible, #customerInfoForm .dropdown.validate:visible');
			$.each (requiredFields, function() {
				validateInput($(this));
			});

			if ($('#customerInfoForm .form-error').length > 0) {
				$('#form-error-msg').html("Please fill out all required fields.").removeClass('noDisplay');
				return false;
			}

			if (errorMsg) {
				alert(errorMsg);
				return false;
			}

			var accountData = {};
			accountData.func = "saveUserInformation";
			accountData.user_email = user_email;
			accountData.first_name = user_first_name;
			accountData.last_name = user_last_name;
			accountData.user_address = address;
			accountData.user_city = city;
			accountData.user_state = state;
			accountData.user_postal_code = postal_code;
			accountData.user_country = country;
			if (highest_level_school_completed != '') {
				accountData.highest_level_school_completed = highest_level_school_completed;
			}
			if (birthday != '') {
				accountData.birthday = birthday;
			}
			<? if ($userIsPilot == "Yes") {?>
				accountData.birthday = birthday;
				accountData.sex = sex;
				accountData.race = race;
				accountData.special_needs = special_needs;
			<? } ?>

			$.ajax({
			  url: "<?=$GLOBALS['wwwBaseDir']?>core/user_management_ajax.php",
			  data: accountData
			}).done(function(response) {
				var success = response.split('|');
				success = success[0];
				if (success !== "TRUE") {
					showAjaxBoxError(dialogBoxTitle, $.trim(response));
				} else {
					showAjaxBoxSuccess(dialogBoxTitle);
					<? if ($_SESSION['account_error']['pilot']) { ?>
					$('#pilotMissingDataError').remove();
					$('#optOut').remove();
					<? } ?>
				}
			});
		});
	});
	function updateErrorMsgStatus() {
		if ($('#customerInfoForm .form-error').length == 0) {
			$('#form-error-msg').html("").addClass('noDisplay');
		}
	}
	function adjustStateRegionInput() {
		if ($('#countryList').dropdown('get value') != 'US') {
			$('#stateList').parent().hide();
			$('#stateList').dropdown('clear');
			$('#region').show();
		}
		else {
			$('#stateList').parent().show();
			$('#region').html('').hide();
		}
	}
</script>

<div class="ui secondary menu">
  <a class="item active" data-tab="first">User Information</a>
  <a class="item" data-tab="second">Update Password</a>
</div>
<div class="ui tab segment active" data-tab="first">
  <div id="customerInfoForm">
        <div class="ui form">
          <div class="six fields">
            <div class="field">
              <label for="first_name">First Name:</label>
              <input type="text" id="first_name" name="first_name" class="validate" value="<?=$_SESSION['user']['user_first_name']?>" required />
            </div>
            <div class="field">
              <label for="last_name">Last Name:</label>
              <input type="text" id="last_name" name="last_name" class="validate" value="<?=$_SESSION['user']['user_last_name']?>" required />
            </div>
            <div class="field">
              <label for="email">Email:</label>
              <input type="email" id="email" name="email" class="validate" value="<?=$_SESSION['user']['user_email']?>" data-validate-type="email" required />
            </div>
          </div>
          <div class="four fields">
            <div class="field">
              <label for="country">Country:</label>
              <?=$countrySelect?>
            </div>
          </div>
          <div class="four fields">
            <div class="field">
              <label for="address">Address:</label>
              <input type="text" id="address" name="address" class="validate" value="<?=$user_address?>" required />
            </div>
          </div>
          <div class="six fields">
            <div class="field">
              <label for="city">City:</label>
              <input type="text" id="city" name="city" class="validate" value="<?=$user_city?>" required />
            </div>
            <div class="field">
              <label for="state">State/Region:</label>
              <?=$stateSelect?>
              <input id="region" name="region" class="validate" placeholder="Region" value="<?=$user_state?>" required />
            </div>
            <div class="field">
              <label for="postal_code">Postal Code:</label>
              <input type="text" id="postal_code" name="postal_code" class="validate" value="<?=$user_postal_code?>" required />
            </div>
          </div>
            </br>
            <? if ($userIsPilot =="Yes") { ?>
						<div id="pilotInfo">
                <div class="pilotData two fields">
                    <div class="field">
                        <? pilotUsersNotice(); ?>
                    </div>
                </div>
                <br/>
                <div class="pilotData four fields">
                    <div class="field">
                        <label for="race">Race:</label>
                        <select id="race" name="race" class="validate ui dropdown" required>
                            <option value="">Select</option>
                                <? foreach ($races as $race) { ?>
                                        <option value="<?=$race?>" <? if ($user_race == $race) echo 'selected'; ?>><?=$race?></option>
                                <? } ?>
                        </select>
                    </div>
                    <div class="field sex-field">
                        <label for="sex">Sex:</label>
                        <select id="sex" name="sex" class="validate ui dropdown" required>
                            <option value="">Select</option>
                                <? foreach ($sexes as $sex) { ?>
                                        <option value="<?=$sex?>" <? if ($user_sex == $sex) echo 'selected'; ?>><?=$sex?></option>
                                <? } ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="birthday">Birth Year:</label>
                        <input type="number" id="birthday" name="birthday" class="validate" placeholder="YYYY" data-validate-type="number" min="<?=$min_birthday?>" max="<?=$max_birthday?>" value="<?=$user_birthday?>" required />
                    </div>
                </div>
                <div class="pilotData four fields">
                    <div class="field">
                        <label for="special_needs">Special Needs:</label>
                        <select id="special_needs" name="special_needs" class="validate ui dropdown" required>
                            <option value="">Select</option>
                            <? foreach ($disabilityCategories as $disabilityCategory) { ?>
                                    <option value="<?=$disabilityCategory?>" <? if ($user_special_needs == $disabilityCategory) echo 'selected'; ?>><?=$disabilityCategory?></option>
                            <? } ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="highest_level_school_completed">Highest Degree or Level of School Completed:</label>
                        <select id="highest_level_school_completed" name="highest_level_school_completed" class="validate ui dropdown" required>
                            <option value="">Select</option>
                            <? foreach ($schoolLevels as $schoolLevel) { ?>
                            	<option value="<?=$schoolLevel?>" <? if ($user_highest_level_school_completed == $schoolLevel) echo 'selected'; ?>><?=$schoolLevel?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
							</div>
            <? } ?>
        </div>
        <br/>
        <? if ($userIsPilot == "No") { ?>
					<div id="optionalInfo">
            <h4 class="ui dividing header">Optional Information</h4>
            <div class="ui form">
                <div class="nonPilotData four fields">
                    <div class="field">
                        <label for="birthday">Birth Year:</label>
                        <input type="number" id="birthday" name="birthday" class="validate" placeholder="YYYY" data-validate-type="number" min="<?=$min_birthday?>" max="<?=$max_birthday?>" value="<?=$user_birthday?>" />
                    </div>
                </div>
                <div class="nonPilotData four fields">
                    <div class="field">
                        <label for="highest_level_school_completed">Highest Degree or Level of School Completed:</label>
                        <select id="highest_level_school_completed" name="highest_level_school_completed" class="validate ui dropdown" required>
                            <option value="">Select</option>
                            <? foreach ($schoolLevels as $schoolLevel) { ?>
                                    <option value="<?=$schoolLevel?>" <? if ($user_highest_level_school_completed == $schoolLevel) echo 'selected'; ?>><?=$schoolLevel?></option>
                            <? } ?>
                        </select>
                    </div>
                </div>
            </div>
					</div>
        <? } ?>

        <div class="inline field">
            <input type="submit" value="Save" name="saveInfoBtn" id="saveInfoBtn" class="ui class blue button" />
            <div id="form-error-msg" class="noDisplay ui left pointing red basic label"></div>
        </div>
    </div>
</div>

<div class="ui tab segment" data-tab="second">
    <form>
      <div id="customerPasswordForm" class="ui form">
        <div class="four fields">
          <?=passwordField("pass_1", "New Password", true)?>
        </div>
        <div class="four fields">
          <?=passwordField("pass_2", "Confirm Password", true)?>
        </div>
        <button name="savePasswordBtn" id="savePasswordBtn" class="ui class blue button">Update Password</button>
      </div>
  </form>
</div>

<?
	require_once($GLOBALS['wwwBaseDir'].'core/template/template_bottom.php');
?>
