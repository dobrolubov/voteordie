<?php

$population = array ();
$votes = array ();
$votesYears = array ();

// load population data

$c = file_get_contents (dirname (__FILE__) . "/../data/data.csv");
$a = explode ("\n", $c);

foreach ($a as $s) {
    if (preg_match ("|^        ([\w\d][\w\d\.\-\s]{5,40})|uis", $s, $ar)) {
	$stateName = trim ($ar [1]);
	$stateName = fixStateName ($stateName);

	$sN = preg_replace ("|^[^\d+]|uis", "", $s);
	$sN = preg_replace ("|(\d)\s(\d)|uis", "$1$2", $sN);
	preg_match_all ("|(\d+)|uis", $sN, $ar);

	if ($stateName == "???") continue;

	for ($i=0; $i<=23; $i++) {
	    @$population [$stateName] [1990 + $i] += $ar [1] [$i*3];
	    @$populationYear [1990 + $i] += $ar [1] [$i*3];
	}
    }
}

$c = file_get_contents (dirname (__FILE__) . "/../data/population.1959-2017.txt");
$a = explode ("\n", $c);

foreach ($a as $s) {
    $ar = explode ("\t", $s);

    if (count ($ar) == 13) {
	$stateName = $ar [1];
	$stateName = preg_replace ("|[\[\]\d]+|", "", $stateName);
	$stateName = trim ($stateName);
	$stateName = fixStateName ($stateName);

	if ($stateName == "???") continue;

	for ($i=0; $i<4; $i++) {
	    @$population [$stateName] [2014 + $i] += $ar [$i + 9];
	    @$populationYear [2014 + $i] += $ar [$i + 9];
	}
    }
}

// load votes data
$votes ["1995"] = array ("year" => 1995, "title" => "1995", "data" => loadVotes (1995, 3));
$votes ["1996"] = array ("year" => 1996, "title" => "1996", "data" => loadVotes (1996, 1));
$votes ["2000"] = array ("year" => 2000, "title" => "2000", "data" => loadVotes (2000, 1));
$votes ["2003"] = array ("year" => 2003, "title" => "2003", "data" => loadVotes (2003, 8));
$votes ["2004"] = array ("year" => 2004, "title" => "2004", "data" => loadVotes (2004, 4));
$votes ["2007"] = array ("year" => 2007, "title" => "2007", "data" => loadVotes (2007, 1));
$votes ["2008"] = array ("year" => 2008, "title" => "2008", "data" => loadVotes (2008, 6));
$votes ["2011"] = array ("year" => 2011, "title" => "2011", "data" => loadVotes (2011, 2));
$votes ["2012"] = array ("year" => 2012, "title" => "2012", "data" => loadVotes (2012, 2));
$votes ["2016"] = array ("year" => 2016, "title" => "2016", "data" => loadVotes (2016, 2));

$results = correlationTable ($votes, $population);


// load votes data
$votes1996 ["1996-1"] = array ("year" => 1996, "title" => "Ельцин Б.Н. (1 тур)", "data" => loadVotes (1996, 1));
$votes1996 ["1996-2"] = array ("year" => 1996, "title" => "Зюганов Г.А. (1 тур)", "data" => loadVotes (1996, 2));
$votes1996 ["1996-3"] = array ("year" => 1996, "title" => "Лебедь А.И.", "data" => loadVotes (1996, 3));
$votes1996 ["1996-4"] = array ("year" => 1996, "title" => "Явлинский Г.А.", "data" => loadVotes (1996, 4));
$votes1996 ["1996-5"] = array ("year" => 1996, "title" => "Жириновский В.В.", "data" => loadVotes (1996, 5));
$votes1996 ["1996-6"] = array ("year" => 1996, "title" => "Ельцин Б.Н. (2 тур)", "data" => loadVotes (1996, 6));
$votes1996 ["1996-7"] = array ("year" => 1996, "title" => "Зюганов Г.А. (2 тур)", "data" => loadVotes (1996, 7));
$votes1996 ["1996-8"] = array ("year" => 1996, "title" => "против всех", "data" => loadVotes (1996, 8));

$results1996 = correlationTable ($votes1996, $population);

echo "\n\n";

foreach ($votes as $voteInfo) {
	$votesData = $voteInfo ["data"];
	$votesTitle = $voteInfo ["title"];
	
	foreach ($votesData as $stateName => $n) {
		$votesToPrint [$stateName] [$votesTitle] = $n;
	}
}

foreach ($votes1996 as $voteInfo) {
	$votesData = $voteInfo ["data"];
	$votesTitle = $voteInfo ["title"];
	
	foreach ($votesData as $stateName => $n) {
		$votesToPrint1996 [$stateName] [$votesTitle] = $n;
	}
}

$content = file_get_contents (dirname (__FILE__) . "/../template/template.html");

$content = str_replace ("%TABLE_RESULTS%", printTable ($results), $content);
$content = str_replace ("%TABLE_RESULTS_1996%", printTable ($results1996), $content);
$content = str_replace ("%TABLE_VOTES%", printTable ($votesToPrint), $content);
$content = str_replace ("%TABLE_VOTES_1996%", printTable ($votesToPrint1996), $content);
$content = str_replace ("%TABLE_POPULATION%", printTable ($population), $content);

file_put_contents (dirname (__FILE__) . "/../docs/index.html", $content);

// functions

function correlationPearson ($xA, $yA) {
    $avgX = array_sum ($xA) / count ($xA);
    $avgY = array_sum ($yA) / count ($yA);
    $sCov = 0;
    $qX = 0;
    $qY = 0;

    foreach ($xA as $k => $x) {
	$y = $yA [$k];
	$sCov += ($x - $avgX) * ($y - $avgY);
	$qX += ($x - $avgX) * ($x - $avgX);
	$qY += ($y - $avgY) * ($y - $avgY);
    }

    $q = sqrt ($qX * $qY);

    return $sCov / $q;
}

function correlationTable ($votes, $population) {
	
	$upToYears = 5;
	$avgs = array ();	
	$results = array ();

	foreach ($votes as $voteInfo) {
		
		$year = $voteInfo ["year"];
		$votesData = $voteInfo ["data"];
		$votesTitle = $voteInfo ["title"];
		
		echo $votesTitle . ":\t";
	
		for ($dYear = -$upToYears; $dYear <=$upToYears; $dYear++) {
			$otherYear = $year + $dYear;
			$dsPopulation = array ();
			$dsVotes = array ();
	
			foreach ($population as $stateName => $years) {
				if (array_key_exists ($otherYear, $population [$stateName])
				&& array_key_exists ($otherYear + 1, $population [$stateName])
				&& array_key_exists ($stateName, $votesData)
				) {
					$p = $population [$stateName] [$otherYear];
					$pNext = $population [$stateName] [$otherYear + 1];
			
					$dsPopulation [$stateName] = ($pNext - $p) ;
			
					$dsVotes [$stateName] = $votesData [$stateName];
				}
			}
		
			if (count ($dsPopulation) > 0 ) {
				$c = correlationPearson ($dsPopulation, $dsVotes);
				$avgs [$dYear] []= $c;
		
				$results [$votesTitle][$dYear] = sprintf ("%.2f", $c);
		
				echo sprintf ("%.2f", $c) . "\t";
				
			} else {
				$results [$votesTitle][$dYear] = "*";
		
				echo "*    \t";
			}
		}
		echo "\n";
	}
	
	echo "avg:\t";
		for ($dYear = -$upToYears; $dYear <=$upToYears; $dYear++) {
		$c = array_sum ($avgs [$dYear]) / count ($avgs [$dYear]);
		echo sprintf ("%.2f", $c) . "\t";
	
		$results ["Среднее"][$dYear] = sprintf ("%.2f", $c);
	}
	
	return $results;
	
}


function loadVotes ($year, $column) {
    global $population;
	
	$voteRow = array ();

    $votesYears []= $year;

    $c = file_get_contents (dirname (__FILE__) . "/../data/voting.".$year.".txt");
    $a = explode ("\n", $c);

    foreach ($a as $s) {
	$ar = explode ("\t", $s);

        if (count ($ar) > 1) {
	    $stateName = $ar [0];
	    $stateName = trim ($stateName);
	    $stateName = fixStateName ($stateName);

	    $n = 0;

	    if (array_key_exists ($column, $ar)) {
		if ($year == 2008) {
		    if (preg_match ("|\(\s*([\d\.]+)|", $ar [$column], $arX)) {
			$n = (float) $arX [1];
		    }
		} else {
		    $n = $ar [$column];
		    $n = str_replace (",", ".", $n);
		    $n = str_replace ("%", "", $n);
		    $n = trim ($n);
		    $n = (float) $n;
		}
	    }

	    if ($n > 0) {
		if (array_key_exists ($stateName, $population)) {
		    $voteRow [$stateName] = $n;
		} else {
		    echo $stateName . "\n";
		}
	    }
	}
    }
	
	return $voteRow;
}


function fixStateName ($s) {
    $xlat = array (
	"г.Москва" => "Москва",
	"г.Санкт-Петербург" => "Санкт-Петербург",
	"Республика Саха (Якутия)" => "Республика Саха",
	"Республика Северная Осетия — Алания" => "Республика Северная Осетия - Алания",

	"Тюменская область без ХМАО и ЯНАО" => "???",
	"Ханты-Мансийский автономный округ — Югра" => "???",
	"Ямало-Ненецкий автономный округ" => "???",

	"Архангельская область без НАО" => "???",
	"Ненецкий автономный округ" => "???",

	"Республика Крым" => "???",
	"Севастополь" => "???",
	"РФ, всего" => "???",

	// 1995
	"Республика Адыгея (Адыгея)" => "Республика Адыгея",
	"Респуб. Северная Осетия - Алания" => "Республика Северная Осетия - Алания",
	"Республика Татарстан (Татарстан)" => "Республика Татарстан",
	"Камчатская область" => "Камчатский край",
	"Пермская область" => "Пермский край",
	"Читинская область" => "Забайкальский край",
	"Город Москва" => "Москва",
	"Город Санкт-Петербург" => "Санкт-Петербург",
	"Агинский Бурятский автоном. округ" => "???",
	"Коми-Пермяцкий АО" => "???",
	"Корякский АО" => "???",
	"Таймырский АО" => "???",
	"Усть-Ордынский Бурятский а. округ" => "???",
	"Ханты-Мансийский а. округ" => "???",
	"Чукотский АО" => "Чукотский автономный округ",
	"Эвенкийский АО" => "???",
	"Ямало-Ненецкий АО" => "???",
	"РОССИЯ" => "???",

	// 1996
	"Агинский Бурятский АО" => "???",
	"Ненецкий АО" => "???",
	"Таймырский (Долгано-Ненецкий) АО" => "???",
	"Усть-Ордынский Бурятский АО" => "???",
	"Ханты-Мансийский АО" => "???",

	// 2003
	"Кабардино-Балкария" => "Кабардино-Балкарская Республика",
	"Карачаево-Черкесия" => "Карачаево-Черкесская Республика",
	"Саха (Якутия)" => "Республика Саха",
	"Тувинский" => "Республика Тыва",
	"Удмуртия" => "Удмуртская Республика",
	"Чечня" => "Чеченская Республика",
	"Чувашия" => "Чувашская Республика",
	"Волгорадская" => "Волгоградская область",
	"Мурманский" => "Мурманская область",
	"Орловский" => "Орловская область",
	"Пермская" => "Пермский край",
	"Сахалинский" => "Сахалинская область",
	"Читинская" => "Забайкальский край",
	"Агинский Бурятский" => "???",
	"Коми-Пермяцкий" => "???",
	"Корякский" => "???",
	"Ненецкий" => "???",
	"Таймырский" => "???",
	"Усть-Ордынский Бурятский" => "???",
	"Ханты-Мансийский" => "???",
	"Эвенкийский" => "???",
	"Ямало-Ненецкий" => "???",
	"Россия" => "???",

	// 2004
	"Чувашская Республика - Чувашия" => "Чувашская Республика",
	"Агинский Бурятский автономный округ" => "???",
	"Коми-Пермяцкий автономный округ" => "???",
	"Корякский автономный округ" => "???",
	"Таймырский (Долгано-Ненецкий) автономный округ" => "???",
	"Усть-Ордынский Бурятский автономный округ" => "???",
	"Ханты-Мансийский автономный округ - Югра" => "???",
	"Эвенкийский автономный округ" => "???",
	"Город Байконур (Республика Казахстан)" => "???",
	"Территория за пределами РФ" => "???",
	"Сумма" => "???",

	// 2007
	"Удмуртская республика" => "Удмуртская Республика",
	"Чувашская республика" => "Чувашская Республика",
	"Чеченская республика" => "Чеченская Республика",
	"Кабардино-Балкарская республика" => "Кабардино-Балкарская Республика",
	"Карачаево-Черкесская республика" => "Карачаево-Черкесская Республика",
	"Байконур" => "???",
	"За пределами РФ" => "???",

	// 2008
	"Кабардино-Балкарская Респ." => "Кабардино-Балкарская Республика",
	"Карачаево-Черкесская Респ." => "Карачаево-Черкесская Республика",
	"Респ. Северная Осетия" => "Республика Северная Осетия - Алания",
	"г. Москва" => "Москва",
	"г. Санкт-Петербург" => "Санкт-Петербург",
	"Еврейская АО" => "Еврейская автономная область",
	"г. Байконур" => "???",
	"Всего" => "???",

	// 2011
	"За пределами РФ*" => "???",

	// 2016
	"Байконур[комм. 3]" => "???",
    );

    if (array_key_exists ($s, $xlat)) {
	return $xlat [$s];
    }

    global $population;
    $t = $s . " область";
    if (array_key_exists ($t, $population)) return $t;

    $t = $s . " край";
    if (array_key_exists ($t, $population)) return $t;

    foreach ($population as $stateName => $v) {
	if (strlen ($s) > 0 && strstr ($stateName, $s)) {
	     return $stateName;
	}
    }

    return $s;
}

function printTable ($t) {

    $colCap = array ();
    foreach ($t as $k => $v) {
	foreach ($v as $i => $j) {
	    $colCap []= $i;
	}
	break;
    }

    $r = "";
    $r .= "<table class='table'>\n";
    $r .= "<tr><th></th>";
    foreach ($colCap as $i) {
	$r .= "<th>$i</th>";
    }
    $r .= "</tr>\n";

    foreach ($t as $k => $v) {
	$r .= "<tr><th>$k</th>";
	foreach ($colCap as $i) {
	    if (array_key_exists ($k, $t) && array_key_exists ($i, $t [$k])) {
		$c = $t [$k] [$i];
	    } else {
		$c = "&nbsp;";
	    }
	    $r .= "<td>" . $c . "</td>";
	}
	$r .= "</tr>\n";
    }
    $r .= "</table>\n";

    return $r;
}

?>