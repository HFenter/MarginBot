		<div class="clrflt"></div>
</div>

<footer class="footer">
      <div class="container">
      	<div class="row">
            <div class="text-muted col-md-4" style="text-align:center;">
                <?=$config['app_name'].' '.$config['app_version'].'.<small>'.$config['app_version_minor'].'</small>';?><br />
                    <a href="#" data-toggle="modal" data-target="#disclaimerModal">Disclaimer</a>
            </div>
            <div class="text-muted col-md-4" style="text-align:center;">
                Like What We're Doing?<br />
                <a href="#" data-toggle="modal" data-target="#donateModal">Donate!</a>
            </div>
            <div class="text-muted col-md-4" style="text-align:center;">
                Need Support?<br /> 
                    <a href="mailto:<?=$config['app_support_email'];?>">Email</a> | <a href="<?=$config['app_support_url'];?>">Forums</a>
            </div>
         </div>
        </div>
      </div>
    </footer>

<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>

<script src="js/sitescripts.0.1.js"></script>


</body>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-48700112-2', 'auto');
  ga('send', 'pageview');

</script>
</html>