<?php
$WIKI_XML_FILE = 'jawiki-latest-pages-articles.xml';
$SQLITE_FILE = 'wiki_articles.sqlite3';
$SQLITE_TABLE_NAME = 'wikipedia';


if(file_exists($SQLITE_FILE)){
    echo "SQLite3 file exists!\nExit...\n";
    die();
}

$db = new SQLite3($SQLITE_FILE);
$db->exec("create table ${SQLITE_TABLE_NAME}(title, abstract)");
$db->exec('begin');

$file = fopen($WIKI_XML_FILE, 'r');

$isInText = false;
$isSkipTitle = false;
while($line = fgets($file)){
    $line = trim($line);
    if(preg_match('/^<page>$/', $line) === 1){
        $tmp['title'] = '';
        $tmp['text'] = '';
        $isInText = false;
        $isSkipTitle = false;
    }else if(preg_match('/^<title>(.+)<\/title>$/', $line, $m) === 1){
        if(preg_match('/^(Wikipedia|Help|ファイル):.+/', $m[1]) === 1)
            $isSkipTitle = true;
        else
            $tmp['title'] = $m[1];
    }else if(preg_match('/^<text xml:space="preserve">(.*)<\/text>$/', $line, $m) === 1 and !$isSkipTitle){
        // TEXT END
        $tmp['text'] = $m[1];
        $isInText = false;
        textEnd($tmp);
    }else if(preg_match('/^<text xml:space="preserve">(.*)$/', $line, $m) === 1 and !$isSkipTitle){
        $tmp['text'] = $m[1] . "\n";
        $isInText = true;
    }else if(preg_match('/^(.*)<\/text>$/', $line, $m) === 1 and !$isSkipTitle){
        // TEXT END
        $tmp['text'] .= $m[1];
        $isInText = false;
        textEnd($tmp);
    }else if(!$isSkipTitle and $isInText){
        $tmp['text'] .= $line . "\n";
    }
}
fclose($file);

$db->exec('commit');
$db->close();

function escapeQuote($str){
    return str_replace("'", "''", $str);
}

function textEnd($tmp){
    // TEXT END
    $text = explode("\n", $tmp['text']);
    for($i = 0; $i < count($text); $i++){
        if($i > 60)
            break;
        if(preg_match('/^({{|}}|\||\* \[\[|{\| )/', $text[$i]) !== 1 and
            preg_match("/'''/", $text[$i]) === 1 and
                $text[$i] !== ''){
            $abstract = $text[$i];
            break;
        }
    }
    $abstract = isset($abstract) ? $abstract : '';

    if(preg_match('/^#REDIRECT \[\[(.+?)\]\]$/', $tmp['text'], $m) === 1)
        $abstract = "{$m[1]}へ転送";

    $replacePattern = array(
        '/\[\[([^\|]+)\]\]/U',
        '/\{\{.+\|(.+)\}\}/U',
        '/\{\{.+\|.+\|(.+)\}\}/U',
        '/\[\[.+\|(.+)\]\]/U',
        "/'''''(.+)'''''/U",
        "/'''(.+)'''/U",
        "/''(.+)''/U"
    );
    $abstract = preg_replace($replacePattern, '$1', $abstract);

    $abstract = htmlspecialchars_decode($abstract, ENT_QUOTES);

    $abstract = strip_tags($abstract);

    $escapedTitle = escapeQuote($tmp['title']);
    $escapedAbstract = escapeQuote($abstract);
    global $db, $SQLITE_TABLE_NAME;
    $db->exec("insert into $SQLITE_TABLE_NAME values('${escapedTitle}', '${escapedAbstract}')");
}

