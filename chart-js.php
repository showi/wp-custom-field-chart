<?php

namespace WpCustomFieldChart;

class ChartJs {

	public $sid;

	function __construct() {
		$this->sid = $this->genid();		
	}

	function enqueue_script() {
		wp_enqueue_script('chartjs', plugins_url() . '/wp-custom-field-chart/js/Chart.min.js',
			array(), '1.0.1-beta.3', false);
	}

	function genid() {
		return uniqid('cfc') . '_';
	}

	function gen_html($attr, $data, $options) {
		return $this->gen_canvas($attr) . $this->gen_script($attr, $data,
				$options);		
	}

	function gen_canvas($attr) {
		$out = '<canvas id="'.$this->sid.'" ';
		foreach (array('width', 'height') as $key) {
			$out .= $key . '="'.$attr[$key].'" ';
		}
		$out .= "/>\n";
		return $out;
	}

	function gen_script($attr, $data, $options=Null) {
		$vardata = $attr['jsvar_data'];
		$varopt = '{}';
		$varobj = $this->sid . 'Object';
		$out = "<script>\n";
		$out .= 'jQuery(window).load(function() {' ."\n";
		$out .= $this->gen_data($attr, $data);
		if (key_exists('jsvar_options', $attr)) {
			#$out .= $this->gen_options($attr, $options);
			$varopt = $attr['jsvar_options'];
		}
		$out .= "var ctx = document.getElementById(\"".$this->sid."\").getContext(\"2d\");\n";
		$out .= "var $varobj = new Chart(ctx).".$attr['kind']."($vardata, $varopt);\n";
		if (key_exists('jsfunc_hook', $attr)) {
			$out .= $attr['jsfunc_hook'] . "($varobj);\n";
		}
		$out .= "});</script>\n";
		return $out;
	}

	function gen_data($attr, $data) {
		$vardata = $attr['jsvar_data'];
		$fields = split(',', $attr['fields']);
		$out = $vardata.".labels=[" . join(',', $data['labels']) . "];\n";
		foreach($fields as $idx => $name) {
			$out .= $vardata.".datasets[$idx].data=[";
			foreach($data['datasets'][$idx] as $key => $value) {
				$out .= "$value,";
			}
			$out .= "];\n";
		}
		return $out;
	}

	function gen_options($attr, $options=Null) {
		if (is_null($options) || $options == '') {
			return '';
		}
		$vardata = $attr['jsvar_options'];
		return "var $vardata=$options;\n";
	}
}
