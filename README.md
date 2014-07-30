wp-custom-field-chart
=====================

Worpress plugin that display custom field other time as chart using Chart.js

Usage
=====

You need some javascript and a wordpress tag.


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
