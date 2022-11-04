#! /usr/bin/env php
<?php
$_dateFormat        = "M d Y";
$_directoryItems    = array();
$_currentDir        = getcwd();

// Modify the %% strings according to your directory structure
if (strpos($_currentDir, '%%firstFolder%%')) {
    $_documentRoot  = '/srv/html/%%firstFolder%%';
} elseif (strpos($_currentDir, '%%secondFolder%%')) {
    $_documentRoot  = '/srv/html/%%secondFolder%%';
}

$_fileWithSubjects  = 'titles.txt';
$_ignoreDotFiles    = true;
$_ignoreItems       = array(
    '.', '..', 'css', 'favicon.ico', 'images',
    'index.html', 'index-php.html', 'index.php',
    'js', 'ls-lRt', '.message', '.message.ftp.txt',
    'scripts', 'theme', $_fileWithSubjects
);
$_location          = '';
$_readSubject       = false;
$_baseURL           = 'https://www.%%your url%%';
$_styleSheet        = '/css/index-generator.css';

// Run this function after variable initialization
$_breadCrumbs     = generateBreadCrumbs($_baseURL, $_documentRoot);

function generateBreadCrumbs($_baseURL = '', $_documentRoot = '')
{
    global $_location;
    $_bc = '';

    $_cwd = str_replace($_documentRoot, '', getcwd());
    $_linkArray = explode('/', $_cwd);

    foreach ($_linkArray as $_link) {
        $_baseURL = $_baseURL . $_link . '/';
        if (empty($_link)) {
            $_link = 'Home';
        }
        $_bc = $_bc . '<a href="' . $_baseURL . '">' . $_link . ' /</a>';
    }
    $_location = $_link;
    return $_bc;
}

function bytesToHuman($_bytes)
{
  $units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

  for ($i = 0; $_bytes > 1024; $i++) {
    $_bytes /= 1024;
  }

  return round($_bytes) . $units[$i];
}

function scanSubject($_file)
{
    $_found = false;
    $_lines = file($_file);
    $_subject = array();

    foreach ($_lines as $_line) {
        if (strpos($_line, 'Subject:') !== false) {
            $_found = true;
            $_subject = explode(': ', $_line, 2);
            break;
        }
    }
    if ($_found and $_subject[1] !== '')
        return $_subject[1];
    else
        return $_file;
}

function generateTableRow($_itemName, $_subject, $_itemDate, $_itemSize, $_itemType = '')
{
    echo '<tr class="d ' . $_itemType . '">';
    echo '<td class="n"><a href="' . $_itemName . '">' . $_subject . '</a></td>';
    echo '<td class="m">' . $_itemDate . '</td>';
    echo '<td class="s">' . $_itemSize . '</td>';
    echo '<td class="t">' . $_itemType . '</td>';
    echo '</tr>' . PHP_EOL;
}

function generateHTMLFooter($_file = '')
{
  if (!empty($_file)) {
    echo 'Reading HTMLFooterhtml from file: ' . $_file . PHP_EOL;
  } else {
    echo '</tbody>
</table>
</body>
<script>
    function searchTable() {
        var input, filter, found, table, tr, td, i, j;
        input = document.getElementById("filter");
        filter = input.value.toUpperCase();
        table = document.getElementById("indexlist");
        tr = table.getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td");
            for (j = 0; j < td.length; j++) {
                if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                }
            }
            if (found) {
                tr[i].style.display = "";
                found = false;
            } else {
                tr[i].style.display = "none";
            }
        }
    }
</script>
</html>
';
  }
}

function generateHTMLHeader($_breadCrumbs,  $_styleSheet, $_file = '')
{
  global $_location;

  if (!empty($_file)) {
    echo 'Reading HTMLHeader.html from file: ' . $_file . PHP_EOL;
  } else {
    echo
    '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="' . $_styleSheet . '" />
    <title>' . $_location . '</title>
  </head>
  <body>
    <div id="navbar">
    <form><input id="filter" onkeyup="searchTable()" type="search" placeholder="Type to filter..." /></form>
    ' . $_breadCrumbs . '
    </div>
    <div id="content">
      <table id="indexlist" cellpadding="0" cellspacing="0">
        <thead>
          <tr>
            <th class="n">Name</th>
            <th class="m">Last Modified</th>
            <th class="s">Size</th>
            <th class="t">Type</th>
          </tr>
        </thead>
        <tbody>
';
  }
}

function processDirectory($_dir, $_ignoreItems = array('.', '..'), $_readSubject = false)
{
  global $_breadCrumbs, $_dateFormat, $_ignoreDotFiles, $_styleSheet;
  $_dirItems  = array_diff(scandir($_dir), $_ignoreItems);
  $_dirList   = array();
  $_fileList  = array();
  $_itemDate  = '';
  $_itemParts = array();
  $_itemSize  = '';
  $_itemType  = '';
  $_subject   = '';

  foreach ($_dirItems as $_item) {
    if (is_dir($_item))
      $_dirList[] = $_item;
    elseif (is_file($_item)) {
      $_fileList[] = $_item;
    }
  }

  foreach ($_dirList as $_item) {
    global $_ignoreDotFiles;

    if ($_item[0] != '.' and $_ignoreDotFiles) {
      $_itemParts = pathinfo($_item);
      $_itemDate  = date($_dateFormat, filemtime($_item));
      $_itemSize  = count(array_diff(scandir($_item), $_ignoreItems)) . ' items';
      $_itemType  = 'dir';
      generateTableRow($_item, $_item . ' /', $_itemDate, $_itemSize, $_itemType);
    }
  }
  foreach ($_fileList as $_item) {
    if ($_item[0] != '.' and $_ignoreDotFiles) {
      $_itemParts = pathinfo($_item);
      $_itemDate  = date($_dateFormat, filemtime($_item));
      $_itemSize  = bytesToHuman(filesize($_item));
      $_itemType  = $_itemParts['extension'];
      $_subject   = ($_readSubject) ? scanSubject($_item) : str_replace('.' . $_itemType, '', $_item);
      generateTableRow($_item, $_subject, $_itemDate, $_itemSize, $_itemType);
    }
  }
}

function processList($_list)
{
    global $_breadCrumbs, $_dateFormat, $_readSubject, $_styleSheet;
    $_itemParts = [];

    $_lines = file($_list);
    foreach ($_lines as $_items) {
        $_itemArray = explode(':', $_items, 2);
        $_item = $_itemArray[0];

        if (file_exists($_item)) {
            $_itemParts = pathinfo($_item);
            $_itemDate = date($_dateFormat, filemtime($_item));
            $_itemSize = bytesToHuman(filesize($_item));
            $_itemType = $_itemParts['extension'];
            $_subject = ($_readSubject) ? scanSubject($_item[0]) : trim($_itemArray[1]);

            generateTableRow($_item, $_subject, $_itemDate, $_itemSize, $_itemType);
        }
    }
}

generateHTMLHeader($_breadCrumbs, $_styleSheet);
if (file_exists($_fileWithSubjects)) {
    $_readSubject = false;
    processList($_fileWithSubjects);
} else {
    processDirectory('./', $_ignoreItems, $_readSubject);
}
generateHTMLFooter();
