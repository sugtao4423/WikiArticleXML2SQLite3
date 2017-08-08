# WikiArticleXML2SQLite3
Wikipediaの全ページのXMLデータである `jawiki-latest-pages-articles.xml` を使い、Wikipedia冒頭にある要約文をSQLite3データベースに突っ込むスクリプト

`jawiki-latest-abstract.xml` がちゃんと要約文を抽出できてなかったので作った。

完全に全てのページの要約文を抽出できるわけではない。

```
$ cat jawiki-latest-pages-articles.xml | grep "<page>" | wc -l
2188542

$sqlite3 wiki_articles.sqlite3 "select count(*) from wikipedia;"
1990962
```

このようにかなり抽出できていないページがある。

冒頭の要約文にあるリンクも除去しようとしたが、記法が多岐にわたっていて難しいので諦めた。

~~Macで動かしてみたら2時間かかっても終わらなかった。~~  
なんか3分半で終わるようになった。なんだったん？  
Debianのサーバで動かしたところ7分で終わった。

