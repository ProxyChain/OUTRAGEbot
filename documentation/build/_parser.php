<?php


function parse_methods($root, $methods)
{
	$param_get = function($parameters, $param)
	{
		foreach($parameters as $item)
		{
			if($item->name == $param)
				return $item;
		}
		
		return null;
	};
	
	$global_methods = [];
	
	foreach($methods as $method => $reflector)
	{
		$inc = false;
		
		$context = array
		(
			"type" => "method",
			
			"metadata" => array
			(
				"method" => $method,
				"file" => substr($reflector->getFileName(), strlen($root) + 1),
				"line" => $reflector->getStartLine(),
			),
		);
		
		$lines = explode("\n", $reflector->getDocComment());
		$parameters = $reflector->getParameters();
		
		$done = false;
		
		foreach($lines as $line)
		{
			$line = trim($line);
			
			switch($line)
			{
				case "/**":
				case "*/":
					break;
				
				default:
				{
					$line = preg_replace("/^\*[\s]{0,}(.*)$/", '$1', $line);
					
					if(substr($line, 0, 1) != "@")
					{
						if(!isset($context["comments"]))
							$context["comments"] = [];
						
						if(strlen($line))
							$context["comments"][] = $line;
						
						break;
					}
					
					$set = [];
					
					if(preg_match('/^\@param\s+void$/', $line, $set))
					{
						if(!isset($context["parameters"]))
							$context["parameters"] = [];
						
						$inc = true;
					}
					
					if(preg_match('/^\@param\s+(.*?)\s+\$(.*?)(\s+(.*))?$/', $line, $set))
					{
						if(!isset($context["parameters"]))
							$context["parameters"] = [];
						
						$inc = true;
						$parameter = $param_get($parameters, $set[2]);
						
						$optional = null;
						$default = null;
						
						if($parameter)
						{
							$optional = $parameter->isOptional();
							
							if($optional)
								$default = $parameter->getDefaultValue();
						}
						
						$context["parameters"][] = array
						(
							"name" => $set[2],
							"type" => $set[1],
							"description" => !empty($set[4]) ? $set[4] : "",
							"optional" => $optional,
							"default" => $default,
						);
					}
					
					if(preg_match('/^\@supplies\s+(.*?)\s+\$(.*?)(\s+(.*))?$/', $line, $set))
					{
						if(!isset($context["supplies"]))
							$context["supplies"] = [];
						
						$inc = true;
						$parent = $reflector->getDeclaringClass();
						
						if(!isset($context["metadata"]["event"]))
						{
							$defaults = $parent->getDefaultProperties();
							
							if(isset($defaults["qualified_name"]))
								$context["metadata"]["event"] = $defaults["qualified_name"];
							else
								$context["metadata"]["event"] = $parent->getShortName();
							
							$context["metadata"]["onevent"] = "on".$context["metadata"]["event"];
						}
						
						$context["supplies"][] = array
						(
							"name" => $set[2],
							"type" => $set[1],
							"description" => !empty($set[4]) ? $set[4] : "",
						);
					}
					
					if(preg_match("/^\@todo/", $line))
						$done = true;
					
					if(preg_match("/^\@example\s+(.*?)\s+(.*)$/", $line, $set))
					{
						$path = $root."/".trim($set[2]);
						
						if(!isset($context["examples"]))
							$context["examples"] = [];
						
						if(file_exists($path))
							$context["examples"][] = [ "type" => $set[1], "contents" => file_get_contents($path) ];
					}
					
					break;
				}
			}
			
			if($done)
				break;
			
			continue;
		}
		
		$context["comments"] = implode("\n", $context["comments"]);
		
		# to end... this loop!
		if($inc)
			$global_methods[$method] = $context;
	}
	
	return $global_methods;
}


function parse_properties($root, $properties)
{
	$global_properties = [];
	
	foreach($properties as $property => $reflector)
	{
		$context = array
		(
			"type" => "property",
			
			"metadata" => array
			(
				"property" => $property,
				"file" => substr($reflector->getFileName(), strlen($root) + 1),
				"line" => $reflector->getStartLine(),
			),
		);
		
		$lines = explode("\n", $reflector->getDocComment());
		$done = false;
		
		foreach($lines as $line)
		{
			$line = trim($line);
			
			switch($line)
			{
				case "/**":
				case "*/":
					break;
				
				default:
				{
					$line = preg_replace("/^\*[\s]{0,}(.*)$/", '$1', $line);
					
					if(substr($line, 0, 1) != "@")
					{
						if(!isset($context["comments"]))
							$context["comments"] = [];
						
						if(strlen($line))
							$context["comments"][] = $line;
						
						break;
					}
					
					$set = [];
					
					if(preg_match('/^\@supplies\s+(.*?)\s+\$(.*?)(\s+(.*))?$/', $line, $set))
					{
						if(!isset($context["supplies"]))
							$context["supplies"] = [];
						
						$inc = true;
						$parent = $reflector->getDeclaringClass();
						
						if(!isset($context["metadata"]["event"]))
						{
							$defaults = $parent->getDefaultProperties();
							
							if(isset($defaults["qualified_name"]))
								$context["metadata"]["event"] = $defaults["qualified_name"];
							else
								$context["metadata"]["event"] = $parent->getShortName();
							
							$context["metadata"]["onevent"] = "on".$context["metadata"]["event"];
						}
						
						$context["supplies"][] = array
						(
							"name" => $set[2],
							"type" => $set[1],
							"description" => !empty($set[4]) ? $set[4] : "",
						);
					}
					
					if(preg_match("/^\@todo/", $line))
						$done = true;
					
					break;
				}
			}
			
			if($done)
				break;
			
			continue;
		}
		
		$context["comments"] = implode("\n", $context["comments"]);
		
		# to end... this loop!
		$global_properties[$property] = $context;
	}
	
	return $global_properties;
}