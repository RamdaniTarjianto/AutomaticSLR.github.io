<?php
    error_reporting(0);
    ini_set('display_errors', 0);
    set_time_limit(10000);
    if(isset($_POST['export'])){
            $key = $_POST['research_name'];
            $keyword = preg_replace('/\s+/', '+', $key);
            // echo $keyword;
            // $query = "https://api.semanticscholar.org/graph/v1/paper/search?query=$keyword&limit=50&fields=title,authors,abstract,url";
            // $url = file_get_contents($query);
            // $data = json_decode($url, true);
            header("Content-type: application/vnd-ms-excel");
            header("Content-Disposition: attachment; filename=hasil.xls");
	}
    

?>

<form action="" method="post">
    <button class="btn-delete" id="export" name="exp">EXPORT</button>
</form>
<table border="1">
    <thead>
        <tr>
            <th>Title</th>
            <th>Abstract</th>
            <th>Publication Year</th>
            <th>Publisher</th>
            <th>Authors</th>
            <th>Result</th>
            <th>Document Link
            </th>
        </tr>
    </thead>

<?php
            $databases = $_POST['databases'];

            foreach ($databases as $databases){ 
                // echo $databases."<br />";
                switch($databases){
                    case 'IEEE':										
                        $query = "http://ieeexploreapi.ieee.org/api/v1/search/articles?apikey=3xh9mgk6qu554d23taxmmn47&format=json&max_records=300&start_record=1&sort_order=asc&sort_field=article_number&abstract=$keyword";
                        $url = file_get_contents($query);
                        $data = json_decode($url, true);
                        $data["data"] = $data['articles'];
                        $temp_url = 'pdf_url';
                        $size = sizeof($data['data']);
						$temp_year = 'publication_year';
                    break;
                    case 'Semantic Scholar':	
                        $query = "https://api.semanticscholar.org/graph/v1/paper/search?query=$keyword&limit=100&fields=title,authors,abstract,url,year";
                        $url = file_get_contents($query);
                        $data = json_decode($url, true);
                        $temp_url = 'url';
                        $size = sizeof($data['data']);
						$temp_year = 'year';
                    break;
                    case "EPC":
                        $query = "https://www.ebi.ac.uk/europepmc/webservices/rest/search?query=TMJ&resultType=core&pageSize=1000&format=json";
                        $url = file_get_contents($query);
                        $data = json_decode($url, true);
                        $size = sizeof($data["resultList"]["result"]);
                    break;
                }

                $results = $_POST['result'];
                foreach ($results as $result){ 
                    for ($i = 0; $i < $size; $i++) {
                        if($databases != "EPC"){
                            $title = $data['data'][$i]['title'];
							$abstract = $data['data'][$i]['abstract'];
							$url = $data['data'][$i][$temp_url];
							$publishedYear = $data['data'][$i][$temp_year];
								if($databases == "IEEE"){
									$publisher = $data['data'][$i]["publisher"];
									$name = "Author: ";
									for ($a = 0; $a < sizeof($data['articles'][$i]["authors"]["authors"]); $a++) {
										$temp = $data['articles'][$i]["authors"]['authors'][$a]['full_name'] . ", ";
										$name .= $temp;
									} 
								}else{
									$name = "Author: ";
									for ($a = 0; $a < sizeof($data['data'][$i]["authors"]); $a++) {
										$temp = $data['data'][$i]["authors"][$a]['name'] . ", ";
										$name .= $temp;
									} 

								}
                        }else{
                            $title = $data["resultList"]["result"][$i]["title"];
                            $abstract = $data["resultList"]["result"][$i]["abstractText"];
                            $url = $data["resultList"]["result"][$i]["fullTextUrlList"]["fullTextUrl"][0]["url"];
                            $publisher = $data["resultList"]["result"][$i]["pubYear"];
                        }
                        $link = "<a href='$url'>$title</a>";
                        $titleAbstract = $title . " " . $abstract;
                        $txt = preg_replace('/\s+/', '+', $titleAbstract);
                        $query = "https://slrdeploy-z35x3o5coa-uc.a.run.app/predict?text=$txt";
                        $curl = curl_init();
    
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $query,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_HTTPHEADER => array(
                                "Content-Length: 0"
                            ),
                        ));
    
                        $response = curl_exec($curl);
                        $err = curl_error($curl);
                        if(sizeof($results) == 1){
                            switch($result){
                                case 'Included':
                                    if($response == "include"){
                                        echo '<tr>';
                                        echo '<th scope="row" class="scope">' , $title , '</th>';
                                        echo '<td>' . $abstract . '</td>';
                                        echo '<td>' . $publishedYear . '</td>';
										echo '<td>' . $publisher . '</td>';
                                        echo '<td>' . $name . '</td>';
                                        if($response == "include"){
                                            echo '<td>' . $response . '</td>';
                                        }else{
                                            echo '<td>' . $response . '</td>';
                                        }
                                        echo '<td>' . $url . '</td>';
                                        echo '</tr>';

                                    }	
                                break;

                                case 'Excluded':
                                    if($response == "exclude"){
                                        echo '<tr>';
                                        echo '<th scope="row" class="scope">' , $title , '</th>';
                                        echo '<td>' . $abstract . '</td>';
                                        echo '<td>' . $publishedYear . '</td>';
										echo '<td>' . $publisher . '</td>';
                                        echo '<td>' . $name . '</td>';
                                        if($response == "include"){
                                            echo '<td>' . $response . '</td>';
                                        }else{
                                            echo '<td>' . $response . '</td>';
                                        }
                                        echo '<td>' . $url . '</td>';
                                        echo '</tr>';

                                    }	
                                break;
                            }
                        }else{
                            echo '<tr>';
                            echo '<th scope="row" class="scope">' , $title , '</th>';
                            echo '<td>' . $abstract . '</td>';
                            echo '<td>' . $publishedYear . '</td>';
							echo '<td>' . $publisher . '</td>';
                            echo '<td>' . $name . '</td>';
                            if($response == "include"){
                                echo '<td>' . $response . '</td>';
                            }else{
                                echo '<td>' . $response . '</td>';
                            }
                            echo '<td>' . $url . '</td>';
                            echo '</tr>';
                        }
                    }
                }
            }
        ?>
</table>