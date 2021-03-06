<?php 

/**************************************** *
 1. Get product data.
 2. Update product data.
**************************************** */

require_once '../db.php';

//1. Get product data.
if(isset($_GET['product_id'])){
    $product_id = htmlspecialchars($_GET['product_id']);
    $sql        = "SELECT * FROM product WHERE product_id = :product_id";
    $stmt       = $db->prepare($sql);
    $stmt->bindParam(':product_id' , $product_id );
    $stmt->execute();

    if($stmt->rowCount() > 0){
      $row              = $stmt->fetch(PDO::FETCH_ASSOC);
      $image_file_name  = htmlspecialchars($row['image_file_name']);


      // Splittar upp alla bilder produkten har till en array: $image_array
      $image_array = explode(" * ", $image_file_name);

      // Variabel som räknar hur många bilder produkten har. Minus ett för att inkludera position "noll" och det sista värdet som alltid är tomt.
      $totalfiles = count($image_array) - 1;

      // Kontrollerar hur många bilder som redan finns för att begränsa hur många bilder som kan laddas upp


    }else{
      // echo 'Produkten finns inte';                               // **
      exit;
    }
}

//2. Update product
if($_SERVER['REQUEST_METHOD'] === 'POST'){

  $product_id    = htmlspecialchars($_POST['product_id']);
  $name          = htmlspecialchars_decode($_POST['name']);
  $description   = htmlspecialchars($_POST['description']);
  $quantity      = htmlspecialchars($_POST['quantity']);
  $price         = htmlspecialchars($_POST['price']);
  $category_id   = htmlspecialchars($_POST['category_id']);
  $image_primary = htmlspecialchars($_POST['image_primary']);
  
  // räkna antalet filer/bilder som ska laddas upp
  $totalfilesNew = count($_FILES['image_file_name']['name']);

  // Räknar ihop totala antalet bilder som ska sparas, max 5st
  $image_total = 0;
  
  // Skapa variabel som ska lagra alla bilder
  $imageCollection = "";

  // Skapa variabel som säger till ifall användaren valt att radera en primär bild
  $image_primary_error = 0;
  
  // Skapa variabel som säger till ifall en bild är för stor
  $tooBig = 0;

  // Skapa variabel som säger till ifall en bild inte har format som är OK
  $imageFormat = 0;

  $headerLocationErrorImage = "";

  // Loopar bilderna som produkten redan har
  for ($i=0; $i < $totalfiles; $i++) { 
    if ($_POST["image_radio_$i"] == "save") { // Sparar gamla bilder i stängen $imageCollection
      if ($image_primary == $i && $i != 0 && $totalfiles > 0) {
        $imageCollection = $image_array[$i] . " * " . $imageCollection; // Sätter primära bilden i början på strängen som sparas på databsen.
      } else {
        $imageCollection .= $image_array[$i] . " * ";
      }
      $image_total++; // Summan av bilder som produkten kommer att ha, om det är 5 så går det inte att lägga till fler bilder
    }
  }





  // Kontrollerar ifall en bild är uppladdad genom att räkna längden på första variabeln i bild-arrayn
  if (strlen(htmlspecialchars(basename($_FILES["image_file_name"]["name"][0]))) > 1) {   //  *** Behövs det här? *** Jag tror det.
    // Loopar över alla filer/bilder
    for($i=0;$i<$totalfilesNew;$i++){
      
      $target_dir = "../images/";

      $addImageCollection = 1;  // Variabel som används för att se ifall bildens sökväg ska läggas till i produktens tabell.
      
      
      $target_file   = $target_dir . basename($_FILES["image_file_name"]["name"][$i]);
      $uploadOk      = 1;
      $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

      
      $check = getimagesize($_FILES["image_file_name"]["tmp_name"][$i]);
      if($check !== false) {
        // echo "<img src='$target_file' class='img-fluid' alt='$name'><br>";  // **
        $uploadOk = 1;
      } else {
        // echo "Det här är ingen bild.<br>";  // **
        $uploadOk = 0;
        $addImageCollection = 0;
        
      }
      
      
      // Check if file already exists           *** Behövs det här? ***
      // if (file_exists($target_file)) {
      //   echo "Den här bilden finns redan.<br>";
      //   $uploadOk = 0;
      // }

      // Check file size
      if ($_FILES["image_file_name"]["size"][$i] > 2000000) {  // Begränsad till 2MB
        // echo "Tyvärr, filen är för stor.<br>";  // **
        $uploadOk = 0;
        $addImageCollection = 0;
        $tooBig++;
      }
      
      // Allow certain file formats
      if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
      && $imageFileType != "gif" ) {
        
        // echo "Tyvärr, bara JPG, JPEG, PNG & GIF är tillåtna filformat.<br>";  // **
        $uploadOk = 0;
        $addImageCollection = 0;
        $imageFormat++;
      }
      
      // Check if $uploadOk is set to 0 by an error
      if ($uploadOk == 0 || $image_total >= 5) {
        
        // echo "Filen gick inte att ladda upp.";  // **
        // if everything is ok, try to upload file
      } else {
      
        if (!move_uploaded_file($_FILES["image_file_name"]["tmp_name"][$i], $target_file)) {
          // echo "Tyvärr, det blev något fel vid uppladdning av fil.<br>";  // **
        } 
        // else {
        //   echo " Bilden ". basename( $_FILES["image_file_name"]["name"][$i]). " har laddats upp.<br>";
        // }
        // echo "</div></tr>";  // **
      }

      // Om $addImageCollection är "1" så kommer bildens sökväg att läggat till under produktens image_file_name kolumn
      if ($addImageCollection == 1 && $image_total < 5) {
        //Sparar alla bilder och separerar bildernas sökväg, med två mellanslag, i en string
        $imageCollection .= htmlspecialchars(basename ($_FILES["image_file_name"]["name"][$i])) . " * ";
      }

      // Error message skapande ifall bilduppladdning blev fel
      $image_total++;
      if ($image_total == 6 || $tooBig == 1 || $imageFormat == 1) {  // Om produktens sparade och nya bilder är fler än 5 skickas man tillbaka till samma sida med varningstext
            $headerLocationErrorImage = "&uppladdning=error";
      }
    }   // Slut på bildernas for-loop.
  }   // Slut på if-sats som kollar ifall bild variabeln är tom.    *** Behövs det här? *** start på rad 105 - Jag tror det.



    $update = "UPDATE product
    SET
    name = :name,
    description = :description,
    quantity = :quantity,
    image_file_name = :imageCollection,
    price = :price,
    category_id = :category_id
    WHERE product_id = :product_id";

    $stmt = $db->prepare($update);

    $stmt->bindParam(':name' , $name );
    $stmt->bindParam(':description'  , $description);
    $stmt->bindParam(':quantity'  , $quantity);
    $stmt->bindParam(':price'  , $price);
    $stmt->bindParam(':category_id'  , $category_id);
    $stmt->bindParam(':product_id'  , $product_id);


    $stmt->bindParam(':imageCollection' , $imageCollection);

    $stmt->execute();

    header("Location:new-update-site.php?product_id=$product_id$headerLocationErrorImage");
    // exit;
//   }else if (isset($_GET['product_id']) == false) {                           //  *Ä**************************Kanske inte ha?
//     echo 'Produkten finns inte';
//     exit;
  } // Slut på updatering av produkt
?>