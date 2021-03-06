<?php
class QueryArticle extends connect{
  private $article;

  public function __construct(){
    parent::__construct();
  }

  public function setArticle(Article $article){
    $this->article = $article;
  }

  
  public function save(){
    // ===== ↓上書き・新規作成で共通の項目はif文の外に移動 ここから↓ =====
        // bindParam用
        $title = $this->article->getTitle();
        $body = $this->article->getBody();
        $filename = null;
    // ===== ↑共通項目の移動 ここまで↑ =====
    
        if ($this->article->getId()){
          // IDがあるときは上書き
    // 元々ここにあった$title, $bodyなどの共通項目の代入はif文外に移動する
          $id = $this->article->getId();
          $stmt = $this->dbh->prepare("UPDATE articles
                    SET title=:title, body=:body, updated_at=NOW() WHERE id=:id");
          $stmt->bindParam(':title', $title, PDO::PARAM_STR);
          $stmt->bindParam(':body', $body, PDO::PARAM_STR);
          $stmt->bindParam(':id', $id, PDO::PARAM_INT);
          $stmt->execute();
        } else {
          // IDがなければ新規作成
    
    // ===== ↓画像保存処理 ここから追加↓ =====
          if ($file = $this->article->getFile()){
            $old_name = $file['tmp_name'];
            $new_name = date('YmdHis').mt_rand();
    
            // アップロード可否を決める変数。デフォルトはアップロード不可
            $is_upload = false;
    
            // 画像の種類を取得する
            $type = exif_imagetype($old_name);
            // ファイルの種類が画像だったとき、種類によって拡張子を変更
            switch ($type){
              case IMAGETYPE_JPEG:
                $new_name .= '.jpg';
                $is_upload = true;
                break;
              case IMAGETYPE_GIF:
                $new_name .= '.gif';
                $is_upload = true;
                break;
              case IMAGETYPE_PNG:
                $new_name .= '.png';
                $is_upload = true;
                break;
            }   
    
            if ($is_upload && move_uploaded_file($old_name, __DIR__.'/../album/'.$new_name)){
              $this->article->setFilename($new_name);
              $filename = $this->article->getFilename();
            }   
          }
    // ===== ↑画像保存処理 ここまで追加↑ =====
    
    // 元々ここにあった$title, $bodyなどの共通項目の代入はif文外に移動する





    
          $stmt = $this->dbh->prepare("INSERT INTO articles (title, body, filename, created_at, updated_at)
                    VALUES (:title, :body, :filename, NOW(), NOW())");
          $stmt->bindParam(':title', $title, PDO::PARAM_STR);
          $stmt->bindParam(':body', $body, PDO::PARAM_STR);
          $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
          $stmt->execute();
        }
      }
    // 省略
 // ===== ↓ここから追加↓：save()メソッドの下にdelete()を追加
 public function delete(){
  if ($this->article->getId()){
    $id = $this->article->getId();
    $stmt = $this->dbh->prepare("UPDATE articles SET is_delete=1 WHERE id=:id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
  }   
}
// ===== ↑ここまで追加↑ =====


    public function find($id){
                $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE id=:id AND is_delete=0");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $article = null;
        if ($result){
          $article = new Article();
          $article->setId($result['id']);
          $article->setTitle($result['title']);
          $article->setBody($result['body']);
          $article->setCreatedAt($result['created_at']);
          $article->setUpdatedAt($result['updated_at']);
        }
        return $article;
      }


    public function findAll(){
            $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE is_delete=0 ORDER BY created_at DESC");

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $articles = array();
        foreach ($results as $result){
          $article = new Article();
          $article->setId($result['id']);
          $article->setTitle($result['title']);
          $article->setBody($result['body']);
          $article->setCreatedAt($result['created_at']);
          $article->setUpdatedAt($result['updated_at']);
          $articles[] = $article;
        }
        return $articles;
      }   


}