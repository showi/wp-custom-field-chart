=== Plugin Name ===
Contributors: showi
Donate link: 
Tags: custom field, chart, javascript
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Make chart from custom field using Chart.js library

== Description ==

This plugin collect data attached to post/article via custom field and make
chart of it.
This plugin use Chart.js for chart drawing (http://www.chartjs.org/)

== Installation ==

Upload zip from admin extension or use wordpress directory
(searching wp-custom-field-chart)

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 0.0.1 =
* Beta Release

== Usage ==

You need some javascript and a wordpress tag.

`
<script>
var mydata = {
    datasets: [
        {
            label: "Humidity",
            fillColor: "rgba(255,73,0,1)",
            strokeColor: "rgba(255,73,0,1)",
            pointColor: "rgba(255,73,0,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
        },
        {
            label: "Temperature",
            fillColor: "rgba(255,73,0,1)",
            strokeColor: "rgba(255,73,0,1)",
            pointColor: "rgba(255,73,0,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(220,220,220,1)",
        },
    ]
};

var myopts = {
    pointDotRadius: 1,
    bezierCurveTension: 0.2,
    barStrokeWidth : 2,
    barValueSpacing : 2,
    barDatasetSpacing : 0,
};

// Optional...
jQuery(window).load(function() {
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.animationEasing = "easeOutBounce";
    Chart.defaults.global.onAnimationComplete = function(){
        alert('Hello');
    }
});
// End optional
</script>

[custom_field_chart width="1000" height="300"
  kind="line" method="track" interval="day" interval_count="31" 
  fields="humidity,temperature" js_data="mydata" js_options="myopts"]
`

1. fields: Custom field separate by comma
1. method: Aggregate method (track, delta, cumulative)
1. js_data: Name of javascript variable holding chart datasets
	(without options and labels)
1. js_options: Name of javascript variable holding chart options
1. kind: Chart type (line or bar)
1. width: Chart width
1. height: Chart Height

== Note ==
Beta software... Interface may change. 
