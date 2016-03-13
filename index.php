<?php
$players_xml = 'data/players.xml'; 
$matches_xml = 'data/matches.xml'; 
require_once('include/class.Glicko2Player.php');


//Glicko2Player([$rating = 1500 [, $rd = 350 [, $volatility = 0.06 [, $mu [, $phi [, $sigma [, $systemconstant = 0.75 ]]]]]]])

if($_SERVER['REQUEST_METHOD']=='POST'){
    $player1 = $_POST['player1'];
    $player2 = $_POST['player2'];
    $result =  explode('-',$_POST['result']);
    if($player1 && $player2 && count($result) == 2 ){

        //save match 
        $xml = new SimpleXMLElement(file_get_contents($matches_xml));
        $match = $xml->addChild('match');
        $match->addAttribute('player1',$player1);
        $match->addAttribute('player2',$player2);
        $match->addAttribute('match', join('-', $result));
        $match->addAttribute('date',date('Y-m-d H:i:s'));
        file_put_contents($matches_xml, $xml->asXML());

        //update Player stats
        $xml = new SimpleXMLElement(file_get_contents($players_xml));

        // decide winner
        $winner = $player1;
        if ( $result[1] > $result[0] ) {
            $winner = $player2;
        }

        $player1_geko = null;
        $player2_geko = null;

        $player1_record;
        $player2_record;

        //search for player in xml file
        foreach ($xml->children() as $player) {
            if($player['name'] !=  $player1 && $player['name'] !=  $player2) continue;

            $player_geko = new Glicko2Player($player['mmr'],100);

            if ( $player['name'] == $player1 ) {
                $player1_geko = $player_geko;
                $player1_record = explode('-',$player['record']);
            }
            else {
                $player2_geko = $player_geko;
                $player2_record = explode('-',$player['record']);
            }
        }

        if ( $player1 == $winner ) {
            $player1_geko->AddWin($player2_geko);
            $player2_geko->AddLoss($player1_geko);
            $player1_record[0] += $result[0];
            $player1_record[1] += $result[1];
            $player2_record[1] += $result[0];
            $player2_record[0] += $result[1];
        }
        else {
            $player2_geko->AddWin($player1_geko);
            $player1_geko->AddLoss($player2_geko);
            $player1_record[1] += $result[1];
            $player1_record[0] += $result[0];
            $player2_record[0] += $result[1];
            $player2_record[1] += $result[0];
        }
        
        $player1_geko->Update();
        $player2_geko->Update();

        foreach ($xml->children() as $player) {
            if($player['name'] !=  $player1 && $player['name'] !=  $player2) continue;

            if ( $player['name'] == $player1 ) {
                $player['mmr'] = round($player1_geko->rating,0);
                $player['record'] = join('-', $player1_record);
            }
            else {
                $player['mmr'] = round($player2_geko->rating,0);
                $player['record'] = join('-', $player2_record);
            }
        }
        file_put_contents($players_xml, $xml->asXML());
    }  
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <title>In House Liga</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <script language="JavaScript" src="js/glicko2.js"></script>

</head>


<body>
<div class="container">
  <div class="jumbotron">
    <h1><center>In House Liga<center></h1>
  </div>


<form class="form-inline text-center" action="index.php" method="post">
  <div class="form-group">
    <label class="sr-only" for="player1">Player1</label>
    <input type="text" class="form-control" type="text" name="player1" placeholder="Player1">
  </div>
  <div class="form-group">
    <label class="sr-only" for="player2">player2</label>
    <input type="text" class="form-control" type="text" name="player2" placeholder="Player2">
  </div>
  <div class="form-group">
    <label class="sr-only" for="result">result</label>
    <input type="text" class="form-control" type="text" name="result" placeholder="result">
  </div>
  <button type="submit" class="btn btn-default">submit</button>
</form>
  <div class="row">
            <h3><center>ranklist<center><h3>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>player1</th>
          <th>player2</th>
          <th>record</th>
        </tr>
      </thead>
      <tbody>
              <?php
                if (file_exists($players_xml)) {
                    $xml = simplexml_load_file($players_xml);
                    $rows = 1;
                    foreach ( $xml->player as $player )
                        {
                                    echo '<tr>';
                                    echo '<th scope="row">' . $rows . '</th>';
                                    echo '<td>' . $player->attributes()[0] . '</td>';
                                    echo '<td>' . $player->attributes()[1] . '</td>';
                                    echo '<td>' . $player->attributes()[2] . '</td>';
                                echo '</tr>';
                                    $rows++;
                        }

                } else {
                    exit("Datei $players_xml kann nicht geöffnet werden.");
                }
              ?>
      </tbody>
    </table>

  <div class="row">
            <h3><center>matches<center><h3>
  </div>

<table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>player1</th>
          <th>player2</th>
          <th>result</th>
        </tr>
      </thead>
      <tbody>
              <?php
                if (file_exists($matches_xml)) {
                    $xml = simplexml_load_file($matches_xml);
                    $rows = 1;
                    foreach ( $xml->match as $match )
                        {
                                    echo '<tr>';
                                      echo '<th scope="row">' . $rows . '</th>';
                                           echo '<td>' . $match->attributes()[0] . '</td>';
                                           echo '<td>' . $match->attributes()[1] . '</td>';
                                           echo '<td>' . $match->attributes()[2]. '</td>';
                                echo '</tr>';
                                    $rows++;
                        }

                } else {
                    exit("Datei $matches_xml kann nicht geöffnet werden.");
                }
              ?>
      </tbody>
    </table>
</div>
</body>
</html>