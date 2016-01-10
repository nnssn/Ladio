# Ladio

### 概要

「ネットラジオサイト ねとらじ」の番組情報をパース、検索するためのPHPライブラリです。
放送開始通知BOT、番組情報の集計、新番組発掘などの用途に使用できます。

### インストール
```php
composer require nnssn/ladio:0.3.*
```

### 使い方

```php
require_once './vendor/autoload.php';

use Nnssn\Ladio\Table;

$table = new Table;
```

### クラス
##### Tableクラス
番組表をあらわすクラスです。ユーザーが直接生成するクラスは
基本的にはこのクラスだけです。

```php
$table->count() //番組数の取得
$table->first() //1つ目のProgramを取得
//ほか、詳しくは後述
```

##### Programクラス
ひとつの番組をあらわすクラスです。ねとらじ番組表取得時にTableインスタンスが自動的に生成します。
Programクラスは以下のプロパティを持ちます。

```php
$this->relation_url   //関連URL
$this->genre          //ジャンル
$this->title          //タイトル
$this->mount          //マウント
$this->start          //放送開始時刻のDateTimeクラス
$this->listener       //現在のリスナー数
$this->total_listener //延べリスナー数
$this->server         //サーバー
$this->port           //ポート
$this->bitrare        //ビットレート
$this->detail_url     //詳細ページのURL
$this->dj             //DJ名
$this->play_url       //再生URL
```

### Tableクラス・サンプル

##### 番組の取得
```php
$count   = $table->count();  //番組数
$program = $table->first();  //最初の番組
$program = $table->last();   //最後の番組
$program = $table->get(2);   //2つ目の番組
$program = $table->sample(); //ランダム

//foreachで番組をひとつずつ取り出すこともできます
foreach ($table as $program) {
    echo $program->title, '<br />';
}
```

##### ソート
```php
$table->sort('listener', Table::SORT_ASC);  //リスナー数昇順でソート
$table->sort('listener', Table::SORT_DESC); //リスナー数降順でソート
$table->sort('start', Table::SORT_DESC);    //放送開始時刻降順でソート
```

##### 検索
```php
$result = $table->find('検索ワード');                  //すべての項目から検索
$result = $table->find('検索タイトル', 'title');       //タイトルから検索
$result = $table->find('検索タイトル', 'title', true); //タイトルから完全一致で検索
echo ($result->count() > 0) ? '検索結果あり' : 'なし'; //検索系メソッドの戻り値は新しいTableインスタンスです
```

##### いずれかの条件に一致する検索
```php
//マウントがmain,もしくはsubの番組がヒットします
$conditions = [
	["mount", "/main"],
	["mount", "/sub"],
];
$result = $table->conditionsAny($conditions, true);
```

##### すべての条件に一致する検索
```php
//二つのマウントを同時に持つ放送はないため、この条件はヒットしません
$conditions = [
	["mount", "/ura"],
	["mount", "/omote"],
];
$result = $table->conditionsAll($conditions, true);
```

##### 正規表現で検索
```php
//関連URLが「したらば掲示板のラジオカテゴリー・id30000番台」の番組を検索
$pattern = '#http://jbbs\.shitaraba\.net/radio/30\d{3,}/#';
$result  = $table->match($pattern, 'relation_url');
```

##### フィルター
独自の定義で番組の検索を行います

```php
//関連URLが設定されていない
//タイトルに音楽がつく
//リスナー数1人以上の放送を抽出
$callback = function(Program $program) {
    return (! $program->relation_url && mb_strpos('音楽', $program->title) !== false
            && $program->listener >= 1);
};
$result = $table->filter($callback);
```

##### メソッドチェーン
```php
//3回に分けて絞り込み
$result = (new Table)->find('hoge', 'title')
        ->find('fuga', 'genre')
        ->find('piyo', 'dj');

//検索からDJ名の取得までを一括で
$dj = (new Table)->sort('start')
        ->find(0, 'listener')
        ->first()
        ->dj();
```

