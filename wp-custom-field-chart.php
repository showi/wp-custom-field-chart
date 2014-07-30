<?php 
/*
Plugin Name: Wp Custom Field Chart
Plugin URI: http://wordpress.org/extend/plugins/wp-custom-field-chart
Description: Add Chart.js and graphs to your WordPress site based on tallies of any numeric custom field over time. Visualize progress toward any goal by day, week, month, or year.
Version: 0.0.1
Author: Joachim Basmaison
Note: Based on Dylan Kuhn Tally wordpress plugin (GPLv2)
Author URI:
Minimum WordPress Version Required: 2.5.1
*/

/*
 Copyright (c) 2005-2007 Dylan Kuhn, 2014 Joachim Basmaison

This program is free software; you can redistribute it
and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE. See the GNU General Public License for more
details.
*/

//namespace WpCustomFieldChart;

/// The default method for tallying
define('CFC_GRAPH_CUMULATIVE_METHOD', 'cumulative');
/// Tally changes to a running total
define('CFC_GRAPH_DELTA_METHOD', 'delta');
/// Don't tally, just track a changing number, like weight
define('CFC_GRAPH_TRACK_METHOD', 'track');

include "chart-js.php";

function custom_field_chart($attr) {
	$attr = wp_parse_args($attr);
	$chartJs = new WpCustomFieldChart\ChartJs();
	add_action('wp_enqueue_script', $chartJs->enqueue_script());
	$class = 'cfc_graph';
	if (isset($attr['class'])) {
		$class = $attr['class'];
	}
	$attr['cht'] = 'list';
	$data =  cfc_graph_url($attr);
	return $chartJs->gen_html($attr, $data, $options) . $list;
}

// 	if ( isset( $atts['cht'] ) && 'list' == $atts['cht'] ) {
// 		return '<span class="' . $class . '">' . cfc_graph_url( $atts ) . '</span>';
// 	} else {
// 		return '<img class="'.$class.'" src="'.cfc_graph_url($atts).'" alt="'.$atts['key'].'" />';
// 	}

function cfc_graph_week2date($year, $week, $weekday=6) {
	$time = mktime(0, 0, 0, 1, (4 + ($week-1)*7), $year);
	$this_weekday = date('w', $time);
	return mktime(0, 0, 0, 1, (4 + ($week-1) * 7 + ($weekday - $this_weekday)), $year);
}

function cfc_graph_url($atts) {
	global $wp_query;

	$atts = wp_parse_args($atts);
	$defaults = array('interval_count' => '6', 'chs' => '200x200', 'cht' => 'bvs');
	$atts = array_merge($defaults, $atts);
	if (!isset($atts['fields'])) return 'Tally Graph: required parameter "key" is missing.';

	// Extract non-chart variables from attributes
	$keys = split(',',$atts['fields']);
	unset($atts['fields']);
	$use_cache = true;
	if (isset($atts['no_cache'])) {
		$use_cache = false;
		unset($atts['no_cache']);
	}
	if (isset($atts['to_date'])) {
		$end_time = strtotime($atts['to_date']);
		if (!$end_time) {
			return 'Tally Graph: couldn\'t read the to_date ' . $atts['to_date'];
		}
		unset($atts['to_date']);
	} else if ($wp_query->post_count > 0) {
		$end_time = strtotime($wp_query->posts[0]->post_date);
	} else {
		$end_time = time();
	}
	if (isset($atts['cfc_interval'])) {
		$cfc_interval = $atts['cfc_interval'];
		unset($atts['cfc_interval']);
	} else {
		$cfc_interval = 'month';
	}
	if ( isset( $atts['label_interval'] ) ) {
		$label_interval = $atts['label_interval'];
		unset( $atts['label_interval'] );
	} else {
		$label_interval = $cfc_interval;
	}
	$method = CFC_GRAPH_CUMULATIVE_METHOD;
	if (isset($atts['method'])) {
		$method = $atts['method'];
		if (!in_array($method, array(CFC_GRAPH_CUMULATIVE_METHOD, CFC_GRAPH_DELTA_METHOD, CFC_GRAPH_TRACK_METHOD))) {
			return 'Tally Graph: Unknown method "' . $method . '"';
		}
		unset($atts['method']);
	}
	$interval_count = $atts['interval_count'];
	unset($atts['interval_count']);

	list($index_gnu_format, $index_mysql_format, $first_day_suffix) = cfc_graph_interval_settings($cfc_interval);

	// Always start on the first day of the starting interval
	// Always end on the first day after the ending interval
	$start_time = strtotime('-'.$interval_count.' '.$cfc_interval,$end_time);
	if ('week' == $cfc_interval) {
		// Wouldn't need this for PHP 5.1 and later
		$start_time = cfc_graph_week2date(date('Y', $start_time), date('W', $start_time) + 1, 1);
		$end_time = cfc_graph_week2date(date('Y', $end_time), date('W', $end_time), 1);
	} else {
		$next_date_prefix = date($index_gnu_format,strtotime('+1 '.$cfc_interval,$start_time));
		$start_time = strtotime($next_date_prefix.$first_day_suffix);
		$next_date_prefix = date($index_gnu_format,strtotime('+1 '.$cfc_interval,$end_time));
		$end_time = strtotime($next_date_prefix.$first_day_suffix);
	}

	// Return cached URL if available
	if ($use_cache) {
		$key_string = implode( ',', $keys ) . $start_time . $end_time . $cfc_interval . $label_interval. serialize($atts);
		$cache_key = 'tally-graph-'.md5( $key_string );
		$cached_url = wp_cache_get($cache_key);
		if ($cached_url) {
			return $cached_url;
		}
	}

	// Tally ho
	$key_counts = array();
	$key_labels = array();
	foreach($keys as $index => $key) {
		$key_counts[$index] = cfc_graph_get_counts($key, $start_time, $end_time, $cfc_interval, $method, $key_labels);
	}

	// Build the chart parameters
	$chd = $day_label_string = $week_label_string = $month_label_string = $year_label_string = '';
	$last_week = $last_month = $last_year = '';
	$first_index = $chd_min = $chd_max = null;
	$day_label_array = Array();
	foreach($key_counts as $index => $counts) {
		if (is_null($first_index)) $first_index = $index;
		if ($index != $first_index) $chd .= '|';
		$comma = '';
		foreach($counts as $date_index => $count) {
			$compare_count = $count;
			if ($index != $first_index && $atts['cht'] == 'bvs') {
				$compare_count = $key_counts[$first_index][$date_index] + $count;
			}
			if (is_null($chd_min) || $compare_count < $chd_min) $chd_min = $compare_count;
			if (is_null($chd_max) || $compare_count > $chd_max) $chd_max = $compare_count;
			$chd .= $comma . $count;
			$comma = ',';
			if ($index == $first_index) {
				$day_label_string .= '|'.$key_labels[$date_index]['day'];
				array_push($day_label_array, $key_labels[$date_index]['day']);
				$week = $key_labels[$date_index]['week'];
				if ( $week != $last_week ) $last_week = $week;
				else $week = ' ';
				$week_label_string .= '|'.$week;
				$month = $key_labels[$date_index]['month'];
				if ($month != $last_month) $last_month = $month;
				else $month = ' ';
				$month_label_string .= '|'.$month;
				$year = $key_labels[$date_index]['year'];
				if ($year != $last_year) $last_year = $year;
				else $year = ' ';
				$year_label_string .= '|'.$year;
			}
		}
	}
	$data = array(
		'datasets' => $key_counts,
		'labels' => $day_label_array
	);
	return $data;
	// Return just the list if requested
	if ( 'list' == $atts['cht'] ) {
		return $chd;
	}

	// Give nonzero minimum values a 10% pad
	$pad_min = floor($chd_min - (($chd_max - $chd_min)/10));
	$chd_min = ($pad_min>0) ? $pad_min : $chd_min;

	// Set Google chart attributes
	// chart data
	$atts['chd'] = 't:' . $chd;
	if (!isset($atts['chds'])) {
		// Provide chart scale
		$atts['chds'] = $chd_min.','.$chd_max;
	}
	if (!isset($atts['chxt'])) {
		// Provide labels
		if ($label_interval == 'year') {
			$atts['chxt'] = 'y,x';
			$atts['chxl'] = '1:'.$year_label_string;
		} else if ($label_interval == 'week') {
			$atts['chxt'] = 'y,x,x';
			$atts['chxl'] = '1:'.$week_label_string.'|2:'.$year_label_string;
		} else if ($label_interval == 'day') {
			$atts['chxt'] = 'y,x,x,x';
			$atts['chxl'] = '1:'.$day_label_string.'|2:'.$month_label_string.
			'|3:'.$year_label_string;
		} else {
			$atts['chxt'] = 'y,x,x';
			$atts['chxl'] = '1:'.$month_label_string.'|2:'.$year_label_string;
		}
	}
	if (!isset($atts['chxr'])) {
		// Provide count range labels at index 0
		$atts['chxr'] = '0,'.$chd_min.','.$chd_max;
	}
	$chart_url = 'http://chart.apis.google.com/chart';
	$separator = '?';
	foreach($atts as $name => $value) {
		$chart_url .= $separator.$name.'='.urlencode($value);
		$separator = '&amp;';
	}
	if ($use_cache) {
		wp_cache_set($cache_key, $chart_url, '', 60 * 60 * 24 * 7);
	}
	return $chart_url;
}

function cfc_graph_get_counts($key, $start_time, $end_time, $interval, $method = CFC_GRAPH_CUMULATIVE_METHOD, &$labels = null) {
	global $wpdb;

	list($gnu_index_format, $mysql_index_format) = cfc_graph_interval_settings($interval);
	$counts = array();
	for($the_time = $start_time; $the_time < $end_time; $the_time = strtotime('+1 '.$interval,$the_time)) {
		$index = date($gnu_index_format,$the_time);
		$counts[$index] = 0;
		if ( is_array( $labels ) ) {
			$labels[$index] = array(
					'day' => date( 'j', $the_time ),
					'week' => date( '\\WW', $the_time ),
					'month' => date( 'M', $the_time ),
					'year' => date( 'Y', $the_time )
			);
		}
	}

	$aggregator = ( CFC_GRAPH_TRACK_METHOD == $method ) ? 'AVG' : 'SUM';

	$query_sql = 'SELECT DATE_FORMAT(p.post_date,\''.$mysql_index_format.'\') AS xdata, '.
			$aggregator . '(pm.meta_value) AS ydata '.
			'FROM '. $wpdb->posts .' p '.
			'JOIN '. $wpdb->postmeta .' pm ON pm.post_id = p.ID '.
			'WHERE pm.meta_key = \''. esc_sql($key) .'\' '.
			'AND p.post_date >= \''.date('Y-m-d',$start_time).'\' '.
			'AND p.post_date < \''.date('Y-m-d',$end_time).'\' '.
			'GROUP BY DATE_FORMAT(p.post_date,\''.$mysql_index_format.'\') '.
			'ORDER BY p.post_date';
	$wpdb->query($query_sql);
	if ($wpdb->last_result) {
		$chd_min = $chd_max = $wpdb->last_result[0]->ydata;
		foreach ($wpdb->last_result as $result) {
			if ($result->ydata < $chd_min) $chd_min = $result->ydata;
			else if ($result->ydata > $chd_max) $chd_max = $result->ydata;
			$counts[$result->xdata] = $result->ydata;
		}
	}

	if (CFC_GRAPH_DELTA_METHOD == $method) {
		$delta_count = cfc_graph_get_count_as_of($key, $start_time);
		for($the_time = $start_time; $the_time < $end_time; $the_time = strtotime('+1 '.$interval,$the_time)) {
			$index = date($gnu_index_format,$the_time);
			$delta_count += $counts[$index];
			$counts[$index] = $delta_count;
		}
	} else if (CFC_GRAPH_TRACK_METHOD == $method) {
		$the_time = $start_time;
		$track_value = 0;
		$index = date($gnu_index_format, $the_time);
		if (!$counts[$index]) {
			$counts[$index] = cfc_graph_get_last_value($key, $start_time);
		}
		do {
			if ($counts[$index]) {
				$track_value = $counts[$index];
			} else {
				$counts[$index] = $track_value;
			}
			$the_time = strtotime('+1 '.$interval,$the_time);
			$index = date($gnu_index_format, $the_time);
		} while ($the_time < $end_time);
	}

	return $counts;
}

function cfc_graph_get_count_as_of($key, $start_time) {
	global $wpdb;

	$query_sql = 'SELECT SUM(pm.meta_value) AS count '.
			'FROM ' . $wpdb->posts . ' p ' .
			'JOIN ' . $wpdb->postmeta . ' pm ON pm.post_id = p.ID ' .
			'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
			'AND p.post_date < \''.date('Y-m-d',$start_time).'\'';
	return $wpdb->get_var($query_sql);
}

function cfc_graph_get_last_value($key, $start_time) {
	global $wpdb;

	$query_sql = 'SELECT pm.meta_value '.
			'FROM ' . $wpdb->posts . ' p ' .
			'JOIN ' . $wpdb->postmeta . ' pm ON pm.post_id = p.ID ' .
			'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
			'AND p.post_date = (SELECT MAX(p.post_date) ' .
			'FROM ' . $wpdb->posts . ' ip ' .
			'JOIN ' . $wpdb->postmeta . ' ipm ON ipm.post_id = ip.ID ' .
			'WHERE pm.meta_key = \'' . esc_sql($key) . '\' ' .
			'AND p.post_date < \''.date('Y-m-d',$start_time).'\')';
	return $wpdb->get_var($query_sql);
}

function cfc_graph_interval_settings($interval) {
	if ($interval == 'year') {
		return array('Y','%Y','-01-01');
	}
	if ($interval == 'week') {
		return array('Y-\\WW','%Y-W%v','-1');
	}
	if ($interval == 'day') {
		return array('Y-m-d','%Y-%m-%d','');
	}
	return array('Y-m','%Y-%m','-01');
}

add_shortcode('custom_field_chart','custom_field_chart');
