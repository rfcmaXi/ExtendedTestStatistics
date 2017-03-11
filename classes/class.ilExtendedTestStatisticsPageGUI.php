<?php

require_once ('Modules/Test/classes/class.ilObjTest.php');

/**
 * Extended Test Statistic Page GUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilExtendedTestStatisticsPageGUI: ilUIPluginRouterGUI
 */
class ilExtendedTestStatisticsPageGUI
{
	/** @var ilCtrl $ctrl */
	protected $ctrl;

	/** @var ilTemplate $tpl */
	protected $tpl;

	/** @var ilExtendedTestStatisticsPlugin $plugin */
	protected $plugin;

	/** @var ilObjTest $testObj */
	protected $testObj;

	/** @var ilExtendedTestStatistics $statObj */
	protected $statObj;

	/**
	 * ilExtendedTestStatisticsPageGUI constructor.
	 */
	public function __construct()
	{
		global $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;

		$lng->loadLanguageModule('assessment');

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'ExtendedTestStatistics');
		$this->plugin->includeClass('class.ilExtendedTestStatistics.php');

		$this->testObj = new ilObjTest($_GET['ref_id']);
		$this->statObj = new ilExtendedTestStatistics($this->testObj, $this->plugin);
	}

	/**
	* Handles all commands, default is "show"
	*/
	public function executeCommand()
	{
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		if (!$ilAccess->checkAccess('write','',$this->testObj->getRefId()))
		{
            ilUtil::sendFailure($lng->txt("permission_denied"), true);
            ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
		}

		$this->ctrl->saveParameter($this, 'ref_id');
		$cmd = $this->ctrl->getCmd('showTestOverview');

		switch ($cmd)
		{
			case "showTestOverview":
            case "showTestDetails":
			case "showQuestionsOverview":
            case "showQuestionDetails":
                $this->prepareOutput();
                $this->$cmd();
                break;
			case "exportEvaluations":
			case "deliverExportFile":
				$this->$cmd();
				break;
			case "applyFilter":
			case "resetFilter":
				$this->prepareOutput();
				$this->showQuestionsOverview();
				break;

			default:
                ilUtil::sendFailure($lng->txt("permission_denied"), true);
                ilUtil::redirect("goto.php?target=tst_".$this->testObj->getRefId());
				break;
		}
	}

	/**
	 * Get the plugin object
	 * @return ilExtendedTestStatisticsPlugin|null
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

    /**
     * Get the statistics object
     * @return     ilExtendedTestStatistics|null
     */
    public function getStatisticsObject()
    {
        return $this->statObj;
    }

	/**
	 * Get the test object id (needed for table filter)
	 * @return int
	 */
	public function getId()
	{
		return $this->testObj->getId();
	}

	/**
	 * Prepare the test header, tabs etc.
	 */
	protected function prepareOutput()
	{
		/** @var ilLocatorGUI $ilLocator */
		/** @var ilLanguage $lng */
		global $ilLocator, $lng;

		$this->ctrl->setParameterByClass('ilObjTestGUI', 'ref_id',  $this->testObj->getRefId());
		$ilLocator->addRepositoryItems($this->testObj->getRefId());
		$ilLocator->addItem($this->testObj->getTitle(),$this->ctrl->getLinkTargetByClass('ilObjTestGUI'));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->testObj->getPresentationTitle());
		$this->tpl->setDescription($this->testObj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', 'tst'), $lng->txt('obj_tst'));
	}

	/**
	 * Show the test overview
	 */
	protected function showTestOverview()
	{
		$this->setOverviewToolbar(ilExtendedTestStatistics::LEVEL_TEST);

		/** @var  ilExteStatTestOverviewTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatTestOverviewTableGUI', $this, 'showTestOverview');
		$tableGUI->prepareData();

		$this->tpl->setContent($tableGUI->getHTML());
		$this->tpl->show();
	}

    /**
     * Show the detailed evaluation for a test
     */
    protected function showTestDetails()
    {
		$this->setDetailsToolbar('showTestOverview');
        $this->ctrl->saveParameter($this, 'details');

        $evaluation = $this->statObj->getEvaluation($_GET['details']);

		/** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showTestDetails');
		$tableGUI->prepareData($evaluation->getDetails());
		$tableGUI->setTitle($evaluation->getShortTitle());
		$tableGUI->setDescription($evaluation->getDescription());

		$this->tpl->setContent($tableGUI->getHTML());
		$this->tpl->show();
    }

    /**
     * Show the questions overview
     */
	protected function showQuestionsOverview()
	{
		$this->setOverviewToolbar(ilExtendedTestStatistics::LEVEL_QUESTION);

		/** @var  ilExteStatQuestionsOverviewTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
        $tableGUI = ilExteStatTableGUI::_create('ilExteStatQuestionsOverviewTableGUI', $this, 'showQuestionsOverview');

		if ($this->ctrl->getCmd() == 'applyFilter')
		{
			$tableGUI->resetOffset();
			$tableGUI->writeFilterToSession();
		}
		elseif (($this->ctrl->getCmd() == 'applyFilter'))
		{
			$tableGUI->resetOffset();
			$tableGUI->resetFilter();
		}

		$tableGUI->prepareData();

        $this->tpl->setContent($tableGUI->getHTML());
        $this->tpl->show();
	}


    /**
     * Show the detailed evaluation for a question
     */
    protected function showQuestionDetails()
    {
		$this->setDetailsToolbar('showQuestionsOverview');
        $this->ctrl->saveParameter($this, 'details');
        $this->ctrl->saveParameter($this, 'qid');

        $evaluation = $this->statObj->getEvaluation($_GET['details']);

		/** @var  ilExteStatDetailsTableGUI $tableGUI */
		$this->plugin->includeClass('tables/class.ilExteStatTableGUI.php');
		$tableGUI = ilExteStatTableGUI::_create('ilExteStatDetailsTableGUI', $this, 'showQuestionDetails');
		$tableGUI->prepareData($evaluation->getDetails($_GET['qid']));
		$tableGUI->setTitle($this->statObj->getSourceData()->getQuestion($_GET['qid'])->question_title);
		$tableGUI->setDescription($evaluation->getTitle());

        $this->tpl->setContent($tableGUI->getHTML());
        $this->tpl->show();
    }

	/**
	 * Set the Toolbar for the overview page
	 * @param string	$level
	 */
	protected function setOverviewToolbar($level)
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar, $lng;

		$ilToolbar->setFormName('etstat_toolbar');
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));

		require_once 'Services/Form/classes/class.ilSelectInputGUI.php';
		$export_type = new ilSelectInputGUI($lng->txt('exp_eval_data'), 'export_type');
		$options = array(
			'excel_overview' => $this->plugin->txt('exp_type_excel_overviews'),
			'excel_details' => $this->plugin->txt('exp_type_excel_details'),
			'csv_test' => $this->plugin->txt('exp_type_csv_test'),
			'csv_questions' => $this->plugin->txt('exp_type_csv_questions'),
		);
		$export_type->setOptions($options);

		$ilToolbar->addInputItem($export_type, true);
		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand('exportEvaluations');
		$button->setCaption('export');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);

		require_once 'Services/Form/classes/class.ilHiddenInputGUI.php';
		$levelField = new ilHiddenInputGUI('level');
		$levelField->setValue($level);
		$ilToolbar->addInputItem($levelField);
	}

	/**
	 * Set the Toolbar for the details page
	 * @param string  $backCmd
	 */
	protected function setDetailsToolbar($backCmd)
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar, $lng;

		$ilToolbar->setFormName('etstat_toolbar');
		$ilToolbar->setFormAction($this->ctrl->getFormAction($this));

		require_once 'Services/UIComponent/Button/classes/class.ilSubmitButton.php';
		$button = ilSubmitButton::getInstance();
		$button->setCommand($backCmd);
		$button->setCaption('back');
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);
	}

	/**
	 * Export the evaluations
	 */
	protected function exportEvaluations()
	{
		$this->plugin->includeClass("export/class.ilExteStatExport.php");

		// set the parameters based on the selection
		switch ($_POST['export_type'])
		{
			case 'csv_test':
				$name = 'test_statistics';
				$suffix = 'csv';
				$type = ilExteStatExport::TYPE_CSV;
				$level = ilExtendedTestStatistics::LEVEL_TEST;
				$details = false;
				break;

			case 'csv_questions':
				$name = 'questions_statistics';
				$suffix = 'csv';
				$type = ilExteStatExport::TYPE_CSV;
				$level = ilExtendedTestStatistics::LEVEL_QUESTION;
				$details = false;
				break;

			case 'excel_details':
				$name = 'detailed_statistics';
				$suffix = 'xlsx';
				$type = ilExteStatExport::TYPE_EXCEL;
				$level = '';
				$details = true;
				break;

			case 'excel_overview':
			default:
				$name = 'statistics';
				$suffix = 'xlsx';
				$type = ilExteStatExport::TYPE_EXCEL;
				$level = '';
				$details = false;
				break;
		}

		// write the export file
		require_once('Modules/Test/classes/class.ilTestExportFilename.php');
		$filename = new ilTestExportFilename($this->testObj);
		$export = new ilExteStatExport($this->plugin, $this->statObj, $type, $level, $details);
		$export->buildExportFile($filename->getPathname($suffix, $name));

		// build the success message with download link for the file
		$this->ctrl->setParameter($this, 'name', $name);
		$this->ctrl->setParameter($this, 'suffix', $suffix);
		$this->ctrl->setParameter($this, 'time', $filename->getTimestamp());
		$link = $this->ctrl->getLinkTarget($this, 'deliverExportFile');
		ilUtil::sendSuccess(sprintf($this->plugin->txt('export_written'), $link), true);
		$this->ctrl->clearParameters($this);

		// show the screen from which the export was started
		switch ($_GET['level'])
		{
			case ilExtendedTestStatistics::LEVEL_QUESTION:
				$this->ctrl->redirect($this, 'showQuestionsOverview');
				break;
			default:
				$this->ctrl->redirect($this, 'showTestOverview');
		}
	}

	/**
	 * Deliver a previously generated export file
	 */
	protected function deliverExportFile()
	{
		// sanitize parameters
		$name = preg_replace("/[^a-z_]/", '', $_GET['name']);
		$suffix = preg_replace("/[^a-z]/", '', $_GET['suffix']);
		$time = preg_replace("/[^0-9]/", '', $_GET['time']);

		require_once('Modules/Test/classes/class.ilTestExportFilename.php');
		$filename = new ilTestExportFilename($this->testObj);
		$path = $filename->getPathname($suffix, $name);
		$path = str_replace($filename->getTimestamp(), $time, $path);

		if (is_file($path))
		{
			ilUtil::deliverFile($path, basename($path));
		}
		else
		{
			ilUtil::sendFailure($this->plugin->txt('export_not_found'), true);
			$this->ctrl->redirect($this);
		}
	}
}
?>