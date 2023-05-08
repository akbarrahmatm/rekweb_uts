<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace NRFramework\Widgets;

defined('_JEXEC') or die;

class FAQ extends Widget
{
	/**
	 * Widget default options
	 *
	 * @var array
	 */
	protected $widget_options = [
		/**
		 * FAQ Settings
		 */

		/**
		 * The questions and answers.
		 * 
		 * Format:
		 * 
		 * [
		 *  	[
		 * 			'question' => 'Question 1',
		 * 			'answer' => 'Answer 1'
		 * 		],
		 *  	[
		 * 			'question' => 'Question 2',
		 * 			'answer' => 'Answer 2'
		 * 		],
		 * ]
		 */ 
		'value' => '',

		/**
		 * Requires "show_toggle_icon" to be enabled to work.
		 * 
		 * Define the initial state of the FAQ.
		 * 
		 * Available values:
		 * 
		 * - first-open: Open the first question
		 * - all-open: Opens all questions
		 * - all-closed: Closes all questions
		 */
		'initial_state' => 'first-open',

		// Set whether to have one question open at a time
		'keep_one_question_open' => true,

		// Set the columns.
		'columns' => 1,

		// Set the gap between the items.
		'item_gap' => 16,

		// Set the gap between the columns.
		'column_gap' => 16,

		// Set whether to display a separator between items
		'separator' => false,

		// Set the separator color
		'separator_color' => '',

		/**
		 * Item Settings
		 */
		// Each item background color.
		'item_background_color' => null,

		// Each item border radius.
		'item_border_radius' => null,

		// Each item padding.
		'item_padding' => null,

		/**
		 * Question
		 */
		// Question font size
		'question_font_size' => null,

		// Each question text color.
		'question_text_color' => null,

		/**
		 * Answer
		 */
		// Answer font size
		'answer_font_size' => null,

		// Each answer text color.
		'answer_text_color' => null,

		/**
		 * Icon Settings
		 */
		/**
		 * Whether to show an icon that can toggle the open/close state of the answer.
		 * 
		 * If disabled, all answers will appear by default.
		 * If enabled, all answers will be hidden by default.
		 */
		'show_toggle_icon' => false,

		/**
		 * Set the icon that will be used.
		 * 
		 * Available values:
		 * - arrow
		 * - plus_minus
		 * - circle_arrow
		 * - circle_plus_minus
		 */
		'icon' => 'arrow',

		/**
		 * Set the icon position.
		 * 
		 * Available values:
		 * 
		 * - right
		 * - left
		 */
		'icon_position' => 'right',

		/**
		 * FAQ Schema
		 */
		// Set whether to generate the FAQ Schema on the page.
		'generate_faq' => false,

		// Custom Item CSS Classes
		'item_css_class' => ''
	];

	/**
	 * Class constructor
	 *
	 * @param array $options
	 */
	public function __construct($options = [])
	{
		parent::__construct($options);

		$this->prepare();
	}

	/**
	 * Prepares the FAQ.
	 * 
	 * @return  void
	 */
	private function prepare()
	{
		if ($this->options['show_toggle_icon'])
		{
			$this->options['show_toggle_icon'] = true;
			$this->options['css_class'] .= ' has-icons';
			$this->options['css_class'] .= ' position-icon-' . $this->options['icon_position'];
			$this->options['css_class'] .= ' has-icon-' . $this->options['icon'];
		}

		if (!empty($this->options['item_background_color']) && $this->options['item_background_color'] !== 'none')
		{
			$this->options['css_class'] .= ' has-item-bg-color';
		}

		if ($this->options['separator'])
		{
			$this->options['css_class'] .= ' has-separator';
		}

		$this->options['css_class'] .= ' ' . $this->options['initial_state'];

		if ($this->options['keep_one_question_open'])
		{
			$this->options['css_class'] .= ' keep-one-question-open';
		}

		if ((int) $this->options['columns'] > 1)
		{
			$this->options['css_class'] .= ' has-columns';
		}

		$this->generateFAQ();
		
		$this->setCSSVars();

		$this->setResponsiveCSS();
	}

	private function generateFAQ()
	{
		// Ensure "generate_faq" is enabled
		if (!$this->options['generate_faq'])
		{
			return;
		}
		
		// Ensure we have questions and answers
		if (!is_array($this->options['value']) && !count($this->options['value']))
		{
			return;
		}

		// Abort if FAQ cannot be compiled
		if (!$faq = $this->getFAQ())
		{
			return;
		}

		// Hook into GSD to add the FAQ
		\JFactory::getApplication()->registerEvent('onGSDBeforeRender', function(&$data) use ($faq)
		{
			try
			{
				// get the data
				$tmpData = $data;
				if (defined('nrJ4'))
				{
					$tmpData = $data->getArgument('0');
				}

				// Append the FAQ Schema
				$tmpData[] = $faq;

				// Ensure unique FAQ
				$tmpData = array_unique($tmpData);
				
				// Set back the new value to $data object
				if (defined('nrJ4'))
				{
					$data->setArgument(0, $tmpData);
				}
				else
				{
					$data = $tmpData;
				}

			} catch (\Throwable $th)
			{
				$this->throwError($th->getMessage());
			}
		});
	}

	/**
	 * Returns the FAQ JSON/LD code.
	 * 
	 * @return  string
	 */
	private function getFAQ()
	{
		$autoload_file = JPATH_ADMINISTRATOR . '/components/com_gsd/autoload.php';
		if (!file_exists($autoload_file))
		{
			return;
		}

		require_once $autoload_file;
		
		// Prepare the FAQ
		$payload = [
			'mode' => 'manual',
			'faq_repeater_fields' => json_decode(json_encode($this->options['value']))
		];
		$payload = new \JRegistry($payload);
		$faq = new \GSD\Schemas\Schemas\FAQ($payload);

		// Get the JSON/LD code of the FAQ
		$json = new \GSD\Json($faq->get());

		// Return the code
		return $json->generate();
	}

	/**
	 * Set widget CSS vars
	 * 
	 * @return  mixed
	 */
	private function setCSSVars()
	{
		if (!$this->options['load_css_vars'])
		{
			return;
		}

		$atts = [];

		if (!empty($this->options['item_background_color']))
		{
			$atts['item-background-color'] = $this->options['item_background_color'];
		}

		if (!empty($this->options['question_text_color']))
		{
			$atts['question-text-color'] = $this->options['question_text_color'];
		}

		if (!empty($this->options['answer_text_color']))
		{
			$atts['answer-text-color'] = $this->options['answer_text_color'];
		}

		if (!empty($this->options['separator_color']))
		{
			$atts['separator-color'] = $this->options['separator_color'];
		}

		if (empty($atts))
		{
			return;
		}

		if (!$css = \NRFramework\Helpers\CSS::cssVarsToString($atts, '.tf-faq-widget.' . $this->options['id']))
		{
			return;
		}

		$this->options['custom_css'] .= $css;
	}


	/**
	 * Sets the CSS for the responsive settings.
	 * 
	 * @return  void
	 */
	private function setResponsiveCSS()
	{
		$initial_breakpoints = [
			'desktop' => [],
			'tablet' => [],
			'mobile' => []
		];
		$responsive_css = $initial_breakpoints;

		// Add padding
		if ($padding = \NRFramework\Helpers\Controls\Spacing::getResponsiveSpacingControlValue($this->options['item_padding'], '--item-padding', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $padding);
		}

		// Add item gap
		if ($gap = \NRFramework\Helpers\Controls\Responsive::getResponsiveControlValue($this->options['item_gap'], '--item-gap', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $gap);
		}

		// Add column gap
		if ($gap = \NRFramework\Helpers\Controls\Responsive::getResponsiveControlValue($this->options['column_gap'], '--column-gap', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $gap);
		}

		// Add item border radius
		if ($borderRadius = \NRFramework\Helpers\Controls\BorderRadius::getResponsiveSpacingControlValue($this->options['item_border_radius'], '--item-border-radius', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $borderRadius);
		}

		// Add Question Font Size
		if ($question_font_size = \NRFramework\Helpers\Controls\Responsive::getResponsiveControlValue($this->options['question_font_size'], '--question-font-size', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $question_font_size);
		}

		// Add Answer Font Size
		if ($answer_font_size = \NRFramework\Helpers\Controls\Responsive::getResponsiveControlValue($this->options['answer_font_size'], '--answer-font-size', 'px'))
		{
			$responsive_css = array_merge_recursive($responsive_css, $answer_font_size);
		}

		if ($css = \NRFramework\Helpers\Responsive::renderResponsiveCSS($responsive_css, '.tf-faq-widget.' . $this->options['id']))
		{
			$this->options['custom_css'] .= $css;
		}
	}

	/**
	 * Returns all CSS files.
	 * 
	 * @return  array
	 */
	public static function getCSS()
	{
		return [
			'plg_system_nrframework/widgets/faq.css'
		];
	}

	/**
	 * Returns all JS files.
	 * 
	 * @param   string  $theme
	 * 
	 * @return  array
	 */
	public static function getJS()
	{
		return [
			'plg_system_nrframework/widgets/faq.js'
		];
	}
}