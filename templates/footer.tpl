		</div>

</div>
<div id="footer">
	<p>Copyright (c) 2008 - 2010 ReachTEL Pty Ltd. All Rights Reserved.</p>
	<p>Page gen time: {$load_time} msec</p>
</div>

{if !empty($loadViz)}  <script type="text/javascript" src="//www.google.com/jsapi"></script>{/if}

  <script type="text/javascript">
{if !empty($loadViz)}        google.load("visualization", "1", {ldelim}packages:["piechart"]{rdelim});{/if}
        {$javascript}
          function newWindow(url, height, width){ldelim}
                newwindow=window.open(url,'name','height='+height+',width='+width);
                if (window.focus) {ldelim}newwindow.focus(){rdelim}
          {rdelim}
  </script>


<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-7036315-1']);
  _gaq.push(['_setDomainName', 'secure.reachtel.com.au']);
  _gaq.push(['_trackPageview']);

  (function() {ldelim}
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  {rdelim})();

</script>

</body>
</html>
