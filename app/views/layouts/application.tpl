<!DOCTYPE html>
<!--[if lt IE 7 ]><html lang=en-us class="no-js ie6"><![endif]--> 
<!--[if IE 7 ]><html lang=en-us class="no-js ie7"><![endif]--> 
<!--[if IE 8 ]><html lang=en-us class="no-js ie8"><![endif]--> 
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="en-us" class="no-js"> <!--<![endif]-->
	<head>
	</head>
	<body style="background: #555; font-size: 14px; margin: 0; padding: 0;">
		<div style="padding: 3px; text-align: center; position: fixed; top: 0; left: 0; width: 100%; background: #000; color: #fff;">
			<?= P3\Version::string(); ?>
		</div>
		<div id="lol" style="-webkit-transition: margin-top 500ms ease-in; border-radius: 15px; width: 600px; margin: -400px auto 0; background: #efefef; padding: 1em 1em 2em; border: 1px solid #111; box-shadow: 10px 10px 15px rgba(0,0,0,0.5);">
			<h1 style="color: red">Edit this layout in: /app/layouts/application.tpl</h1>

			<p><strong>You can also override this layout, or disable it entirely - Global, or by controller [and/or] action</strong></p>

			<h2>Action Output: </h2>
			<div style="margin-left: 2em; background: #ddd; border: 1px solid #333; border-radius: 5px; margin-top: 2em; padding: .5em 1em;">
				<?= $this->yield() ?>
			</div>

			<h2>Sample Buffer (Checkout the action view file): </h2>

			<div style="margin-left: 2em; background: #ddd; border: 1px solid #333; border-radius: 5px; margin-top: 2em; padding: .5em 1em;">
				<?= $this->yield('sample') ?>
			</div>

			<h2>A "partial":  These can be rendered by any template (layout, action_view, or another partial)</h2>

			<div style="margin-left: 2em; background: #ddd; border: 1px solid #333; border-radius: 5px; margin-top: 2em; padding: .5em 1em;">
				<?= $this->render(['partial' => 'shared/test', ['locals' => ['foo' => 'bar']]]); ?>
			</div>

			<h2>A "partial" with a collection!  (picture a record collection ;] )</h2>

			<div style="margin-left: 2em; background: #ddd; border: 1px solid #333; border-radius: 5px; margin-top: 2em; padding: .5em 1em;">
				<?php 
					$collection = [
						(object)(['name' => 'Tim']),
						(object)(['name' => 'Eric']),
					];
				?>
				<?= $this->render(['partial' => 'shared/person', ['collection' => $collection]]); ?>
			</div>
		</div>
		<script type="text/javascript">
			// <!-- <![CDATA[  Yea.. I got bored
			document.getElementById('lol').style.marginTop = '40px';
			// ]]> -->
		</script>

	</body>
</html>