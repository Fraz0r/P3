<p>I am a partial!</p>

<p>Find my ass Here: <strong><?= $this->_path; ?></strong></p>

<p>If a forward slash is the first character in the path for a partial, it's context is taken to the root file system.</p>

<p>If there are <em>no</em> forward slashes in the path, it's context is taken from it's parent (defaulting to the root app view path, if none exists)</p>

<p>If there are <em>any</em> forward slashes in the path, it's context is taken from the root app view path.</p>

<p>Locals test: $foo = <?php var_dump($foo); ?>
</p>