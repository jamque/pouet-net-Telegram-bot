<?php

/* TO DO
- Al saludar consultar https://api.pouet.net/v1/stats/ y decir
estadísticas interesantes.
- "que hay de nuevo" consultar 
https://api.pouet.net/v1/front-page/latest-added/
o 
https://api.pouet.net/v1/front-page/latest-released/
Dan como resultado 24 producciones
- Quizas "que es lo mejor" 
consultar https://api.pouet.net/v1/front-page/alltime-top/
*/

class Main extends TelegramApp\Module {
/**********************/
// Este hooks se ejecuta cuando no encuentra un comando creado
/**********************/
	protected function hooks(){
		if($this->telegram->text_has(["hi", "hello", "hola"]))
		{
			$this->_hello_world();
		}
		if($this->telegram->text_has("joshua"))
		{
			$this->_wopr();
		}
		if($this->telegram->text_has(["help","ayuda","ajuda"]))
		{
			$this->help();
		}
		if($this->telegram->text_command() && $this->telegram->text_regex("ID_{N:id}"))
		{
			$id = $this->telegram->input->id;
			$this->showOne($id);
		}
	}
/**********************/
// Respuestas a palabras clave
/**********************/
	private function _hello_world(){
		$this->telegram->send
			->text($this->strings->get("welcome"), "HTML")
			->disable_web_page_preview(true)
		->send();
		$this->help();
		$this->end();
	}
	
	private function _wopr(){
		$this->telegram->send->text($this->strings->get("wopr"), "HTML")->send();
		$this->end();
	}

/**********************/
// Comandos clave
/**********************/
	public function start(){
		$this->_hello_world();
	}
	
	protected function new_member($user){
		$this->telegram->send
			->text_replace($this->strings->get("hello"), $user->first_name)
		->send();
	}
	
	public function help(){
		$this->telegram->send->text($this->strings->get("help"), "HTML")
		->send();

		$this->end();
	}
	public function prod(){
		$numargs = func_num_args();
		//$this->telegram->send->text($numargs)->send();
		$arg_list = func_get_args();
		$txt_tofind = "";
		for ($i = 0; $i < $numargs; $i++)
		{
			$txt_tofind.= $arg_list[$i]."%20";
		}
		$txt_tofind = rtrim($txt_tofind,"%20");
		//$this->telegram->send->text($txt_tofind)->send();	
		$this->showlist("prod",$txt_tofind);
	}

	public function demo(){
		$numargs = func_num_args();
		//$this->telegram->send->text($numargs)->send();
		$arg_list = func_get_args();
		$txt_tofind = "";
		for ($i = 0; $i < $numargs; $i++)
		{
			$txt_tofind.= $arg_list[$i]."%20";
		}
		$txt_tofind = rtrim($txt_tofind,"%20");
		//$this->telegram->send->text($txt_tofind)->send();	
		$this->showlist("demo",$txt_tofind);
	}

	public function intro(){
		$numargs = func_num_args();
		//$this->telegram->send->text($numargs)->send();
		$arg_list = func_get_args();
		$txt_tofind = "";
		for ($i = 0; $i < $numargs; $i++)
		{
			$txt_tofind.= $arg_list[$i]."%20";
		}
		$txt_tofind = rtrim($txt_tofind,"%20");
		//$this->telegram->send->text($txt_tofind)->send();	
		$this->showlist("intro",$txt_tofind);
	}

/**********************/
// Funciones propias
/**********************/
// https://core.telegram.org/bots/api#formatting-options

	/**
	https://api.pouet.net/
		/param method string de comando
		/param dataIN array de datos a la peticion
	**/
	private function _loadPOUETData($method, $dataIN)
	{
		$url_base = "https://api.pouet.net/v1/";
		$curl_handler = curl_init();
		
		/**** Prepara la linea URL de la peticion ****/
		$url_full = $url_base.$method."?";

		$url_opt="";
		foreach($dataIN as $key => $value)
		{
			$url_opt.=$key."=".$value."&";
		}
		$url_opt = rtrim($url_opt,"&");
		$url_full.=$url_opt;
		
		/**** Enciende el curl. Peticion ****/
		//$this->telegram->send->text($url_full)->send();
		curl_setopt($curl_handler,CURLOPT_URL,$url_full);
		curl_setopt($curl_handler,CURLOPT_CUSTOMREQUEST,"GET");
		curl_setopt($curl_handler,CURLOPT_RETURNTRANSFER,true); // Calladito!

		if(!$resultadoJSON = curl_exec($curl_handler))
		{
			$this->telegram->send->text("POUET API Problems!.")->send();
		}
		curl_close($curl_handler);
		/**** Devuelve el resultado ****/
		$fulldata = json_decode($resultadoJSON,true);
		return $fulldata;
	}
	
	//https://www.php.net/manual/en/function.func-get-args.php
	private function showlist($type, $txt_tofind){
		if (empty($txt_tofind)) {
			$this->telegram->send->text($this->strings->get("prod_no"), "HTML")->send();
		}
		else
		{
			$dataIN = array();
			$dataIN["q"] = $txt_tofind;
			$data = $this->_loadPOUETData("search/prod", $dataIN);
			// No dona resultats
			if(isset($data["error"]))
			{
				$this->telegram->send->text($this->strings->get("prod_error"), "HTML")->send();
				return;
			}
			// 100 o més resultats
			if (count($data["results"]) >= 100)
			{
				$this->telegram->send->text_replace($this->strings->get("prod_100results"), urldecode($txt_tofind))->send();
				return;
			}
			$Totalreply = "";
			$Nresults = 0;
			$FirstID = 0;
			// Si ya encuentra solo 1
			if (count($data["results"]) == 1)
			{
				foreach($data["results"] as $key => $value)
				{
					$this->showOne($value["id"]);
					return;
				}
			}			
			foreach($data["results"] as $key => $value)
			{
				$replyToTelegram = "";
				// Filtro de tipo
				$found = 0;
				if ($type == "prod") $found++;
				switch ($type){
					case "demo":
						if (in_array("demo", $value["types"])) { $found++; break;}
						break;
					case "intro":
						if (in_array("intro", $value["types"])){ $found++; break;}
						if (in_array("32b", $value["types"])) { $found++; break;}
						if (in_array("64b", $value["types"])) { $found++; break;}
						if (in_array("128b", $value["types"])) { $found++; break;}
						if (in_array("256b", $value["types"])) { $found++; break;}
						if (in_array("512b", $value["types"])) { $found++; break;}
						if (in_array("1k", $value["types"])) { $found++; break;}
						if (in_array("4k", $value["types"])) { $found++; break;}
						if (in_array("8k", $value["types"])) { $found++; break;}
						if (in_array("16k", $value["types"])) { $found++; break;}
						if (in_array("32k", $value["types"])) { $found++; break;}
						if (in_array("40k", $value["types"])) { $found++; break;}
						if (in_array("64k", $value["types"])) { $found++; break;}
						if (in_array("96k", $value["types"])) { $found++; break;}
						if (in_array("100k", $value["types"])) { $found++; break;}
						if (in_array("128k", $value["types"])) { $found++; break;}
						if (in_array("256k", $value["types"])) { $found++; break;}
						break;
					default:
						break;
				}
				if ($found == 0) continue;
				//$replyToTelegram .= "/ID_".$value["id"];
				//$replyToTelegram .= " - ".$value["name"];
				$replyToTelegram .= "<b>".$value["name"]."</b>";
				$replyToTelegram .= " - /ID_".$value["id"];
				$first = true;
				$groups = "";
				foreach($value["groups"] as $key2 => $value2)
				{
					if ($first)
					{
						$first = false;
						$groups .= " by ";
					}					
					$groups .= $value2["name"].",";
				}
				if ($groups == "")
				{
					$groups = " [Creator unknown]";
				}
				$groups = rtrim($groups,",");
				$replyToTelegram .= "\n  ".$groups;
				if (count($value["types"]))
				{
					$replyToTelegram .= "\n     -";
					foreach($value["types"] as $key2 => $value2)
					{
						$replyToTelegram .= " ".$value2." |";
					}
					$replyToTelegram = rtrim($replyToTelegram," |");
					$replyToTelegram .= " -";
				}
				if (count($value["platforms"]))
				{
					foreach($value["platforms"] as $key2 => $value2)
					{
						$replyToTelegram .= " ".$value2["name"]." |";
					}
					$replyToTelegram = rtrim($replyToTelegram,"|");
				}				
				$replyToTelegram .= "\n";
				$Nresults ++;
				// Resposta massa llarga
				if (strlen($Totalreply)+strlen($replyToTelegram) >= 4096)
				{
					$this->telegram->send->text($Totalreply,"HTML")->send();
					$Totalreply = $replyToTelegram;
					//$this->telegram->send->text_replace($this->strings->get("prod_toomuch"), $Nresults)->send();
					//return;
				}
				else
				{
					$Totalreply .= $replyToTelegram;
				}
			}
			$Totalreply .= "----- ".$Nresults." ".$type.$this->strings->get("found");
			$this->telegram->send->text($Totalreply,"HTML")->send();
		}
	}
	
	private function showOne($ID)
	{
		$dataIN = array();
		$dataIN["id"] = $ID;
		$data = $this->_loadPOUETData("prod", $dataIN);

		$caption = $data["prod"]["name"];
		$first = true;
		foreach($data["prod"]["groups"] as $key => $value)
		{
			if ($first)
			{
				$first = false;
				$caption .= " by ";
			}
			$caption .= $value["name"].",";
		}
		$caption = rtrim($caption,",");
		if (count($data["prod"]["types"]))
		{
			$caption .= "\n-";
			foreach($data["prod"]["types"] as $key => $value)
			{
				$caption .= " ".$value." |";
			}
			$caption = rtrim($caption,"|");
			$caption .= " -";
		}
		if (count($data["prod"]["platforms"]))
		{
			foreach($data["prod"]["platforms"] as $key => $value)
			{
				$caption .= " ".$value["name"]." |";
			}
			$caption = rtrim($caption,"|");
		}		
		if (count($data["prod"]["placings"]))
		{
			$party = $data["prod"]["placings"][0]["party"]["name"];
			$year = $data["prod"]["placings"][0]["year"];
			$position = $data["prod"]["placings"][0]["ranking"];
			if ($position > 90) $position = 0; // No ranked
			if ($position != 0)
			{
				$lastnumber = substr($position, -1);
				if (intval($lastnumber) < 4)
				{
					$lastnumber = "prod_position".$lastnumber;
				}
				else
				{
					$lastnumber = "prod_position4";
				}
				//$this->telegram->send->text($lastnumber,"HTML")->send();	
				$position = $position.$this->strings->get($lastnumber);
			}
			if ($party != "")
			{
				$caption .= "\n[".$party." ".$year;
				if ($position !=0)
				{
					$caption.= " - ".$position;
				}
				$caption.= "]";
			}
		}
		$caption .= "\n";	
		$caption .= '<a href="https://www.pouet.net/prod.php?which='.$data["prod"]["id"].'">'.$this->strings->get("link_pouet").'</a> - ';
		$video = "";
		foreach($data["prod"]["downloadLinks"] as $key => $value)
		{
			if ($value["type"] == "youtube")
			{
				$video = $value["link"];
				break;
			}
		}
		if ($video != "")
		{
			$caption .='<a href="'.$video.'">'.$this->strings->get("link_youtube").'</a> - ';
		}
		if ($data["prod"]["download"] != "")
		{
			$downld = $data["prod"]["download"];
			// Que al Telegram no le gusta lo ftp
			$downld = str_replace("ftp://", "http://", $downld);
			$caption .= '<a href="'.$downld.'">'.$this->strings->get("link_download").'</a>';
		}
		//file($type, $file, $caption = NULL, $keep = FALSE)
		if (isset($data["prod"]["screenshot"]))
		{
			$this->telegram->send
			  ->text($caption,"HTML")
			  ->disable_web_page_preview(true)
			  ->file("photo",$data["prod"]["screenshot"]);
		}
		else
		{
			$caption = $this->strings->get("prod_noscreenshot")."\n".$caption;
			$this->telegram->send
			  ->disable_web_page_preview(true)
			  ->text($caption,"HTML")
			  ->send();		
		}
	}
}

?>
