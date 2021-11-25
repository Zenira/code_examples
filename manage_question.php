<?
/**
 * This page allows editing assessment questions.
 *
 * @author       Lindsay Sauer <redacted@email.com>
 * @package      admin
 * @subpackage   other
 * @version      1.0
 */

$pageHeader = "Manage Questions";

/**
 * @ignore
 */
if(false) {
	require_once('../core/template/template_top.php');
	require_once('../core/class/html_table.php');
	require_once('../core/other_includes/edit_in_place.php');
	require_once('../core/functions/assessment.php');
} else {
	if(!isset($GLOBALS['wwwBaseDir'])) require_once('../core/other_includes/set_base_dir.php');
	require_once($GLOBALS['wwwBaseDir'] . 'core/template/template_top.php');
	require_once($GLOBALS['wwwBaseDir'] . 'core/class/html_table.php');
	require_once($GLOBALS['wwwBaseDir'] . 'core/functions/assessment.php');
}
$limitedView = ($_SESSION['privilege']['view_questions'] || strpos($_SESSION['site']['site_url'], 'test') !== false);
if ($limitedView) $pageHeader = "View Questions";

verify('admin', true, '', true, true);
$encoding = 'UTF-8';
?>
<? if($_SESSION['privilege']['edit_questions'] && !$limitedView) { ?>
	<input type="button" id="addNewButton" name="addNewButton"  value="New Question" onclick="newQuestion();" class="ui right floated blue button" />
<? } ?>
<h1 id="pageHeader" class="ui dividing header">
	<?=$pageHeader?>
</h1>
<style>
	.ui.checkbox input.hidden+label{
		opacity: 1;
	}
	.ui.checkbox input:checked~.box:before, .ui.checkbox input:checked~label:before, .ui.checkbox input:checked~.box:after, .ui.checkbox input:checked~label:after,
	.ui.radio.checkbox .box:before, .ui.radio.checkbox label:before {
		opacity: 0.5!important;
	}
	#manageQuestions th:first-child { min-width: 50px;}

	#newQuestionForm .ui.dropdown, #editQuestionForm .ui.dropdown { width: 200px; }
	#newQuestionForm .ui.dropdown.fluid, #editQuestionForm .ui.dropdown.fluid { width: auto; }
	#addAnswerBtn { margin-left: 25px; }
	#answersDiv .ui.radio.checkbox { width: 100%; }
	#answersDiv #completeSentences { float: right; }
	#answersDivContainer { margin-top: 10px; }
</style>
<?
if(isset($_REQUEST['search'])) {
	$_SESSION['manage_questions']['search'] = mysqlEscapeString(trim($_REQUEST['search']));
}
if(!isset($_SESSION['manage_questions']['search']) || trim($_SESSION['manage_questions']['search']) == '') {
	$_SESSION['manage_questions']['search'] = 'showNone';
}
$search = '';
if($_SESSION['manage_questions']['search'] != 'showNone') {
	$search = $_SESSION['manage_questions']['search'];
}
$_SESSION['manage_questions']['certification'] = iconv($encoding, 'ASCII//TRANSLIT', stripslashes(trim($_REQUEST['searchText'])));
$_SESSION['manage_questions']['question'] = iconv($encoding, 'ASCII//TRANSLIT', stripslashes(trim($_REQUEST['searchQuestionText'])));
?>
<div class="form-filter ui styled fluid accordion">
  <div class="title">
    <i class="filter icon"></i> Filters
  </div>
  <div class="content active">
    <form name="manageQuestionsSearch" action="<?=$_SERVER['PHP_SELF']?>" method="post">
        <div id="certSearchDiv" class="mini ui labeled icon input">
            <div class="ui label">
                Certification
            </div>
            <input type="text" id="searchText" name="searchText" value="<?=$_SESSION['manage_questions']['certification']?>" placeholder="Search..." onkeypress="if(event.keyCode == 13) manageQuestionsSearchSubmit(this.value);" >
            <i class="search icon"></i>
        </div>
        <input type="submit" name="searchButton" value="Search" onClick="manageQuestionsSearchSubmit(document.manageQuestionsSearch.searchText.value);" class="ui tiny button" />
        <input type="button" value="Clear" onclick="clearCertSearch();" class="tiny ui button" >
        <br><br>
        <div id="questionSearchDiv" class="mini ui labeled icon input">
            <div class="ui label">
                Question
            </div>
            <input type="text" id="searchQuestionText" name="searchQuestionText" value="<?=$_SESSION['manage_questions']['question']?>" placeholder="Search..." onkeypress="if(event.keyCode == 13) manageQuestionsSearchSubmit(this.value);" >
            <i class="search icon"></i>
        </div>
        <input type="button" value="Search" onclick="manageQuestionsSearchSubmit(document.manageQuestionsSearch.searchQuestionText.value);" class="tiny ui button" >
        <input type="button" value="Clear" onclick="clearQuestionSearch();" class="tiny ui button" >
        <input type="hidden" name="search" value="" />
    </form>
  </div>
</div>
<?
$qstr = "
		Select Distinct question_text, assessment_question.question_id, cross_certification_assessment.question_sequence_id, image_path
		From assessment_question
			Inner Join assessment_question_text On assessment_question_text.question_text_id = assessment_question.question_text_id
			Left Join cross_certification_assessment On cross_certification_assessment.question_id = assessment_question.question_id
			Left Join certification On certification.cert_id = cross_certification_assessment.cert_id
			Left Join assessment_image On assessment_image.image_id = assessment_question.image_id
		";
if($_SESSION['manage_questions']['search'] == 'showNone') {
	$qstr .= "
		Where 1 = 0
	";
} elseif($_SESSION['manage_questions']['search'] != 'showAll') {
	$qstr .= "
		Where (1=0 ";
	if ($_SESSION['manage_questions']['certification'] != '') {
		$qstr .= "
			Or cert_name Like '%" . mysqlEscapeString($_SESSION['manage_questions']['certification']) . "%'
			Or cert_title Like '%" . mysqlEscapeString($_SESSION['manage_questions']['certification']) . "%'
			Or cert_description Like '%" . mysqlEscapeString($_SESSION['manage_questions']['certification']) . "%'
		";
	}
	if ($_SESSION['manage_questions']['question'] != '') {
		$qstr .= "Or question_text Like '%" . mysqlEscapeString($_SESSION['manage_questions']['question']) . "%' ";
		$qstr .= "Or assessment_question.question_id = '" . mysqlEscapeString($_SESSION['manage_questions']['question']) . "'";
		$qstr .= "Or cross_certification_assessment.question_sequence_id = '" . mysqlEscapeString($_SESSION['manage_questions']['question']) . "'";
		$qstr .= "Or image_path Like '%" . mysqlEscapeString($_SESSION['manage_questions']['question']) . "%' ";
	}
	$qstr .= ')';
}
	if($_SESSION['privilege']['view_questions']) {
		 $qstr .= 'And cert_assessment_question_status = "Active"';
	}
$qstr .= "
	$qstrWhere
	Group By question_text, assessment_question.question_id, cross_certification_assessment.question_sequence_id
	Order By cross_certification_assessment.question_sequence_id
";
$query = new Query($qstr, true);
$queryRows = $query->getResults();

$rows = array();
$rowClasses = array();
$addImageBtnHTML = '<button class="addImageBtn ui icon button" data-tooltip="Add an Image"><i class="plus icon"></i></button>';
foreach($queryRows as $queryRowId=>$queryRow) {
	$row = array();
	$questionId = $queryRow['question_id'];
	$questionSequence = $queryRow['question_sequence_id'];
	$errorReport = 'Question ' . $questionSequence . ': ' . trim($queryRow['question_text']) . "\r\n";

	$qstr = "
			Select distinct cert_name, cert_title
			From certification
				Inner Join cross_certification_assessment On cross_certification_assessment.cert_id = certification.cert_id
			Where cross_certification_assessment.question_sequence_id = '$questionSequence'
			And cross_certification_assessment.question_id = '$questionId'
				And cert_assessment_question_status = 'Active'
			";
	$query->setQueryString($qstr);
	$certRows = $query->getResults();

	// Answers
	$qstr = "
			Select answer_text, answer_correct, answer_order
			From assessment_answer
				Inner Join assessment_answer_text On assessment_answer_text.answer_text_id = assessment_answer.answer_text_id
			Where question_id = '$questionId'
				And answer_status = 'Active'
			";
	$query->setQueryString($qstr);
	$answerRows = $query->getResults();

	if($_SESSION['privilege']['view_questions']) {
		 $row['question_id'] = '<span class="showForMobileOnly bold">Question #</span>'.$questionSequence;
	}
	else {
		$row['question_id'] = '<span class="showForMobileOnly bold">Question #</span>'.$questionId;
	}

	$row['question_text'] = $queryRow['question_text'];
	$row['answers'] = '<div class="ui form">
						  <div class="answerFields grouped fields">
							<label></label>';
	foreach ($answerRows as $answerRow) {
		$checked = ($answerRow['answer_correct'] == "Yes") ? ' checked="checked"' : '';
		$row['answers'] .= '<div class="field">
							  <div class="ui radio checkbox">
								<input type="radio" name="answer_'.$queryRowId.'" value="'.$answerRow['answer_id'].'" disabled'.$checked.'>
								<label>'.formatAssessmentAnswers($answerRow['answer_text']).'</label>
							  </div>
							</div>';
		// Error report
		$errorReport .= "- ".trim($answerRow['answer_text']);
		if($answerRow['answer_correct'] == 'Yes') $errorReport .= '   <---- correct';
		$errorReport .= "\r\n";
	}
	$row['answers'] .= '</div></div>';

	$image = ($queryRow['image_path'] <> '') ? $queryRow['image_path'] : '-';
	$row['image'] = '<span class="questionImageName noDisplay">'.$image.'</span>';
	if ($queryRow['image_path'] != '') {
		$row['image'] .= imageInclude($GLOBALS['wwwBaseDir'].'core/images/quiz_images/'.$queryRow['image_path'], 'Click to view assessment image', '', 'expandImage clickable ui small rounded image', 'data-image-path="'.$queryRow['image_path'].'" data-question-id="'.$questionId.'"');

		//'<img class="expandImage clickable ui small rounded image" src="'.$GLOBALS['wwwBaseDir'].'core/images/quiz_images/'.$queryRow['image_path'].'" alt="Click to view assessment image" title="Click to view assessment image" data-image-path="'.$queryRow['image_path'].'" data-question-id="'.$questionId.'">';
	}
	else {
		if($_SESSION['privilege']['edit_questions'] && !$limitedView) {
			$row['image'] .= $addImageBtnHTML;
		}
	}

	$row['appears_in_certs'] = '';
	$appearsInCerts = '';
	foreach ($certRows as $certRow) {
		$appearsInCerts .= $certRow['cert_name'].'<br>';
	}
	if (count($certRows) > 0) {
		$row['appears_in_certs'] = '<div class="noDisplay">'.$appearsInCerts.'</div>';
		$row['appears_in_certs'] = '<div class="showForMobileOnly bold">Appears in Cert(s):</div>'.$appearsInCerts;
	}
	else {
		$row['appears_in_certs'] = '<div class="noDisplay"><span style="color:red; font-style:italic;">This question does NOT appear in any certifications</span></div>';
		$row['appears_in_certs'] = '<div class="showForMobileOnly bold">Appears in Cert(s):</div><span style="color:red; font-style:italic;"> This question does NOT appear in any certifications</span>';
	}

	if(($_SESSION['privilege']['edit_questions'] && !$limitedView) || $_SESSION['privilege']['report_question_error']) {
		$row['modify_questions'] = '';
	}

	if($_SESSION['privilege']['edit_questions'] && !$limitedView) {
		$onClick = 'onclick="manageQuestions(' . $questionId . ');"';
		$row['modify_questions'] .= '<input type="button" class="ui tiny button" value="Edit" ' . $onClick . ' />';
	}
	if($_SESSION['privilege']['report_question_error']) {
		$errorReport = escapeSingleQuoteForJavascript(escapeDoubleQuoteForJavascript(urlencode(html_entity_decode($errorReport))));
		$onClick = 'onclick="reportQuestionError(' . $questionId . ', \'' . $certRow['cert_name'] . '\', \'' . $certRow['cert_title'] . '\', \'' . $errorReport . '\', \'' . $queryRow['image_path'] . '\', \'' . $_SESSION['site']['site_url'] . '\', \'' . $_SESSION['user']['user_last_name'] . ', ' . $_SESSION['user']['user_first_name'] . '\', \'' . $_SESSION['user']['user_email'] . '\');"';
		$row['modify_questions'] .= '<br><br><input type="button" class="ui tiny button" value="Report Error" ' . $onClick . ' />';
	}

	$rowClasses[] = "questionRow_".$questionId;

	$rows[] = $row;
}
?>
<script language="javascript" type="text/javascript">
	$(document).ready (function() {
		// Add a new answer on New Question and Edit Question forms
		$('html').on ('click', '#addAnswerBtn', function() {
			var totalAnswers = $('#answersDiv input[name="answer"]').length;
			$(this).before('<div class="field">'+
				  '<div class="ui radio checkbox">'+
					'<input type="radio" name="answer" id="answer_'+totalAnswers+'">'+
					'<label><textarea name="answer_text" id="answer_text_'+totalAnswers+'" rows="1"></textarea></label>'+
				  '</div>'+
				'</div>');
		});

		// View image
		$('#manageQuestions').on ('click', '.expandImage', function() {
			var $thisImage = $(this);
			var src = $thisImage.attr('src');
			var questionId = $thisImage.attr('data-question-id');
			var imagePath = $thisImage.attr('data-image-path');
			var modal_settings = {
				id: "viewImageModal",
				title: 'Question Image: '+imagePath,
				content: '<img class="ui centered rounded image" src="'+src+'" alt="Assessment Image" title="Assessment Image">',
				size: 'small',
				buttons: {
					<? if($_SESSION['privilege']['edit_questions']) { ?>
					"Remove Image from Question": {
						action: function() {
							if (confirm("This will remove the image from ANY assessment question that is using the image. Are you CERTAIN you wish to continue?")) {
								$.ajax({
								  url: "<?=$GLOBALS['wwwBaseDir']?>admin/manage_questions_ajax_editor.php",
								  data: {
									remove_image: 1,
									question_id: questionId
								  }
								}).done(function(response) {
									if (response == "TRUE") {
										$thisImage.remove();
										$('.questionRow_'+questionId+' .questionImageName')
											.html('')
											.after('<?=$addImageBtnHTML?>');
									}
								});
							}
						},
						color: 'red',
						icon: 'x',
					},
					<? } ?>
					"Close": { action: "close"}
				}
			};
			modal(modal_settings);
		});

		// Add Image to question
		$('#manageQuestions').on ('click', '.addImageBtn', function() {
			var $thisButton = $(this);
			var questionId = $thisButton.closest('tr').attr('class').replace('questionRow_', '');

			var modal_settings = {
				id: "addImageModal",
				title: 'Add Image to Question',
				load_url: '<?=$GLOBALS['wwwBaseDir']?>core/other_includes/file_upload.php?func=getUploadTemplate&template=assessment-image',
				size: 'small',
				buttons: {
					<? if($_SESSION['privilege']['edit_questions']) { ?>
					"Add Image to Question": {
						action: function() {
							var image_path = $('#image').val();
							if (image_path == '') return false;

							$.ajax({
							  url: "manage_questions_ajax_editor.php",
							  data: {
								add_image: 1,
								question_id: questionId,
								image: image_path
							  }
							}).done(function(response) {
								if (response == "TRUE") {
									var randonNum = Math.floor(Math.random() * 10000);
									closeModal('addImageModal');
									$thisButton.remove();
									$('.questionRow_'+questionId+' .questionImageName')
										.html(image_path)
										.after('<img class="expandImage clickable ui small rounded image" src="<?=$GLOBALS['wwwBaseDir']?>core/images/quiz_images/'+image_path+'?version='+randonNum+'" alt="Click to view assessment image" title="Click to view assessment image" data-image-path="'+image_path+'" data-question-id="'+questionId+'">');
								}
							});
						},
						color: 'green',
						icon: 'plus',
						force_validation: true
					},
					<? } ?>
					"Close": { action: "close"}
				}
			};
			modal(modal_settings);
		});
	});

	function clearCertSearch() {
		document.manageQuestionsSearch.searchText.value = '';
		manageQuestionsSearchSubmit(document.manageQuestionsSearch.searchQuestionText.value);
	}
	function clearQuestionSearch() {
		document.manageQuestionsSearch.searchQuestionText.value = '';
		manageQuestionsSearchSubmit(document.manageQuestionsSearch.searchText.value);
	}
	function manageQuestionsSearchSubmit(searchText) {
		document.manageQuestionsSearch.searchButton.disabled = true;
		document.manageQuestionsSearch.search.value = searchText;
		document.manageQuestionsSearch.submit();
	}
	<? if($_SESSION['privilege']['edit_questions']) { ?>
		function newQuestion() {
			var modal_settings = {
				id: "searchForExistingQuestionModal",
				title: 'Create New Question',
				size: 'small',
				load_url: "manage_questions_ajax_editor.php?question_search_form=1",
				buttons: {
					"Save": {
						color: 'green',
						force_validation: true,
						disabled: true,
						visible: false,
						id: 'saveQuestionBtn'
					},
					"Cancel": { action: "close"}
				},
				closable: false
			};
			modal(modal_settings);
		}
		function manageQuestions(questionId) {
			var modal_settings = {
				id: "editQuestionModal",
				title: 'Edit Question',
				size: 'small',
				load_url: "manage_questions_ajax_editor.php?question_edit_form=1&question_id="+questionId,
				buttons: {
					"Save": {
						action: function() {
							var questionText = $('#questionText').val();
							var $checkedAnswerEle = $('#answersDiv input[type=radio][name=answer]:checked');
							var assessmentImage = $('#image').val();
							var completeSentences = $('#completeSentences').checkbox("is checked");
							var preserveNewlines = $('#preserveNewlines').checkbox("is checked");

							var requiredFields = $('#newQuestionForm input[required]:visible, #newQuestionForm .dropdown.validate:visible');
							$.each (requiredFields, function() {
								validateInput($(this));
							});

							if ($('#newQuestionForm .form-error').length > 0) {
								showAjaxBoxError($checkedAnswerEle, "Please fill out all required fields.");
								return false;
							}

							// Passes basic validation
							if ($checkedAnswerEle.length == 0) {
								showAjaxBoxError($checkedAnswerEle, "Please select a correct answer.");
								return false;
							}

							var answers = new Array();
							$('#answersDiv textarea[name=answer_text]').each (function() {
								var thisAnswer = $(this).val();
								if (thisAnswer !== '') {
									answers.push(thisAnswer);
								}
							});
							if (answers.length < 2) {
								showAjaxBoxError($checkedAnswerEle, "Please enter the answers to the question.");
								return false;
							}
							var correctAnswerId = $checkedAnswerEle.attr('id').replace('answer_', '');
							var pilotQuestion = $('#pilotQuestion').val();

							var questionData = {};
							questionData.update_question = true;
							questionData.questionId = questionId;
							questionData.questionText = questionText;
							questionData.answers = answers;
							questionData.correctAnswerId = $checkedAnswerEle.attr('id').replace('answer_', '');
							questionData.image = assessmentImage;
							questionData.completeSentences = completeSentences;
							questionData.preserveNewlines = preserveNewlines;

							$.ajax({
							  url: "manage_questions_ajax_editor.php",
							  data: questionData
							}).done(function(response) {
								if (response == "TRUE") {
									closeModal('editQuestionModal');
									location.reload();
								}
								else {
									var modal_settings = {
										id: "updateQuestionErrorModal",
										title: 'Error',
										error: true,
										size: 'mini',
										content: response,
										buttons: {
											"Close": { action: "close"}
										},
										closable: false
									};
									modal(modal_settings);
								}
							});
						},
						color: 'green',
						icon: 'check',
						force_validation: true,
						id: 'updateQuestionBtn'
					},
					"Cancel": { action: "close"}
				}
			};
			modal(modal_settings);
		}
	<? } ?>
</script>
<?
if($_SESSION['privilege']['report_question_error']) {
	echo jsInclude($GLOBALS['wwwBaseDir'].'core/javascript/assessment_question_report_error_form.js');
}

$table = new Html_Table;
$table->_tableID = 'manageQuestions';
$table->_tableShowTotalRows = true;
$table->_tableTotalRowsIdentifier = "Question";
$table->_rowClasses = $rowClasses;
$headers = array('ID', 'Question', 'Answers', 'Image', 'Appears in Cert(s)');
if(($_SESSION['privilege']['edit_questions'] && !$limitedView) || $_SESSION['privilege']['report_question_error']) {
	$headers[] = '';
}
echo $table->createHtmlTable($headers, $rows);
echo 'Complete: ' . date('Y-m-d H:i:s');
require_once($GLOBALS['wwwBaseDir'] . 'core/template/template_bottom.php'); ?>
