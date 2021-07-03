<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="<?= $charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->get('title') ?></title><?php $this->headerAssets(); ?>
</head>
<body><?php $this->bodyBeginAssets();
// $this->addHeaderAsset('test', 'https://code.jquery.com/jquery-3.6.0.min.js', 'inline');
$this->view();
$this->footerAssets(); ?>
</body>
</html>