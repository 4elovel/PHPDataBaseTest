<?php

$pdo = new PDO("mysql:host=localhost;dbname=productsDB", 'root', '');
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
$query = "CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    name TEXT
);
CREATE TABLE IF NOT EXISTS Categories (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    name TEXT
);
CREATE TABLE IF NOT EXISTS Products (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    name TEXT,
    price INT,
    category_id INT,
    FOREIGN KEY(category_id) REFERENCES Categories(id)
);
CREATE TABLE IF NOT EXISTS Carts (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT,
    product_id INT,
    FOREIGN KEY(user_id) REFERENCES Users(id),
    FOREIGN KEY(product_id) REFERENCES Products(id)
);";
$pdo->exec("INSERT INTO Categories(name) VALUES ('test')");
$pdo->exec("INSERT INTO Products(name,price,category_id) VALUES ('test',1000,1)");

for ($i = 1; $i < 11; $i++) {
    CreateUser($pdo,'test'.$i);
}
for ($i = 1; $i < 11; $i++) {
    CreateCart($pdo,$i,1);
}

function CreateUser(PDO $pdo,string $name) : void
{
    $buf = $pdo->prepare("INSERT INTO Users (name) Values (:name)");
    $buf->bindValue(':name',$name);
    $buf->execute();
}
function CreateCart(PDO $pdo,int $user_id,int $product_id): void
{
    $buf = $pdo->prepare("INSERT INTO Carts (user_id,product_id) Values (:user_id,:product_id)");
    $buf->bindValue(':user_id',$user_id);
    $buf->bindValue(':product_id',$product_id);
    $buf->execute();
}
echo "<br>Всі користувачі: <br>";
ShowAllUsers($pdo);
echo "<br>Вся корзина: <br>";
ShowAllItemsInCart($pdo);
echo "<br>Корзина користувача з id 4: <br>";
ShowAllItemsInCartUser($pdo, 4);
echo "<br>Категорії, продукти яких добавив користувач з id 4 в корзину: <br>";
GetCategoriesAddedToCartByUser($pdo, 4);
echo "<br>Користувачі, які купили товар з id 4: <br>";
GetUsersWhoBoughtProduct($pdo, 4);
echo "<br>Категорії, які не додані до корзини користувача з id 4: <br>";
GetCategoriesNotInUserCart($pdo, 4);
function ShowAllUsers(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT * FROM Users');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Всі користувачі: <br>";
    foreach ($users as $user) {
        echo "ID: " . $user['id'] . ", Ім'я: " . $user['name'] . "<br>";
    }
}
function ShowAllItemsInCart(PDO $pdo): void
{
    $query = 'SELECT Carts.id, Users.name AS user_name, Products.name AS product_name, Categories.name AS category_name, Products.price
              FROM Carts
              INNER JOIN Users ON Carts.user_id = Users.id
              INNER JOIN Products ON Carts.product_id = Products.id
              INNER JOIN Categories ON Products.category_id = Categories.id';
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "User: " . $item['user_name'] . "<br>";
        echo "Product: " . $item['product_name'] . "<br>";
        echo "Category: " . $item['category_name'] . "<br>";
        echo "Price: " . $item['price'] . "<br>";
        echo "<br>";
    }
}
function ShowAllItemsInCartUser(PDO $pdo, int $userId): void
{
    $query = "SELECT Carts.id, Users.name AS user_name, Products.name AS product_name, Categories.name AS category_name, Products.price
              FROM Carts
              INNER JOIN Users ON Carts.user_id = Users.id
              INNER JOIN Products ON Carts.product_id = Products.id
              INNER JOIN Categories ON Products.category_id = Categories.id
              WHERE Users.id = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "User: " . $item['user_name'] . "<br>";
        echo "Product: " . $item['product_name'] . "<br>";
        echo "Category: " . $item['category_name'] . "<br>";
        echo "Price: " . $item['price'] . "<br>";
        echo "<br>";
    }
}
function GetCategoriesAddedToCartByUser(PDO $pdo, int $userId): void
{
    $query = "SELECT DISTINCT Categories.name AS category_name
              FROM Carts
              INNER JOIN Products ON Carts.product_id = Products.id
              INNER JOIN Categories ON Products.category_id = Categories.id
              WHERE Carts.user_id = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo $item['category_name'] . "<br>";
    }
}
function GetUsersWhoBoughtProduct(PDO $pdo, int $productId): void
{
    $query = "SELECT DISTINCT Users.*
              FROM Carts
              INNER JOIN Users ON Carts.user_id = Users.id
              WHERE Carts.product_id = :productId";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':productId', $productId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "ID: " . $item['id'] . ", Name: " . $item['name'] . "<br>";
    }
}
function GetCategoriesNotInUserCart(PDO $pdo, int $userId): void
{
    $query = "SELECT Categories.id AS category_id, Categories.name AS category_name, Products.id AS product_id, Products.name AS product_name
              FROM Categories
              LEFT JOIN Products ON Categories.id = Products.category_id AND Products.id NOT IN (
                  SELECT product_id FROM Carts WHERE user_id = :userId
              )";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
         $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        if ($item['product_id']) {
            echo "Category ID: " . $item['category_id'] . ", Category Name: " . $item['category_name'] . "<br>";
            echo "Product ID: " . $item['product_id'] . ", Product Name: " . $item['product_name'] . "<br>";
            echo "<br>";
        }
    }
}