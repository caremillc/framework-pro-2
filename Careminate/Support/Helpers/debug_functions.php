<?php declare(strict_types=1);

if (!function_exists('dd')) {
    function dd(...$vars): never
    {
        $isCli = php_sapi_name() === 'cli';

        if (!$isCli) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Debug Dump</title>
            <style>
            body{font-family:monospace;background:#2d2d2d;color:#f8f8f2;padding:20px}
            .dd-container{margin-bottom:20px;border:1px solid #444;border-radius:4px;overflow:hidden}
            .dd-header{background:#444;color:#f8f8f2;padding:8px 15px;font-weight:bold;display:flex;justify-content:space-between;align-items:center}
            .dd-controls button{margin-left:5px;background:#333;color:#f8f8f2;border:none;padding:4px 8px;border-radius:4px;cursor:pointer}
            .dd-controls button:hover{background:#555}
            .dd-content{padding:15px;overflow:auto;max-height:500px}
            .dump{white-space:pre; font-family:monospace;}
            .string{color:#a6e22e}.number{color:#ae81ff}.boolean{color:#66d9ef}.null{color:#f92672}
            .array{color:#fd971f}.object{color:#a1efe4}.property{color:#e6db74}
            .collapsible{cursor:pointer}.collapsed>.collapse-content{display:none}
            .collapse-toggle:before{content:"+"}.expanded>.collapse-toggle:before{content:"-"}
            .collapse-preview{color:#75715e;font-style:italic}
            .search-container{margin-bottom:10px;display:flex;gap:5px}
            .search-input{flex-grow:1;background:#333;color:#f8f8f2;border:1px solid #444;padding:6px;border-radius:4px}
            .search-btn{background:#444;color:#f8f8f2;border:none;padding:6px 10px;border-radius:4px;cursor:pointer}
            .search-btn:hover{background:#555}
            .highlight-search{background-color:#ff8;color:#000}
            </style>
            <script>
            document.addEventListener("DOMContentLoaded", function(){
                document.querySelectorAll(".collapse-toggle").forEach(toggle=>{
                    toggle.addEventListener("click",function(){
                        const parent=this.closest(".collapsible");
                        parent.classList.toggle("expanded");
                        parent.classList.toggle("collapsed");
                    });
                });
                document.querySelectorAll(".expand-all").forEach(btn=>{
                    btn.addEventListener("click",function(){
                        const container=this.closest(".dd-container");
                        container.querySelectorAll(".collapsible").forEach(e=>{e.classList.add("expanded"); e.classList.remove("collapsed");});
                    });
                });
                document.querySelectorAll(".collapse-all").forEach(btn=>{
                    btn.addEventListener("click",function(){
                        const container=this.closest(".dd-container");
                        container.querySelectorAll(".collapsible").forEach(e=>{e.classList.remove("expanded"); e.classList.add("collapsed");});
                    });
                });
                document.querySelectorAll(".search-form").forEach(form=>{
                    form.addEventListener("submit",function(e){
                        e.preventDefault();
                        const container=this.closest(".dd-container");
                        const term=this.querySelector(".search-input").value.toLowerCase();
                        if(!term) return;
                        container.querySelectorAll(".highlight-search").forEach(el=>{el.outerHTML=el.innerHTML;});
                        function highlight(el){
                            if(el.tagName==="SCRIPT") return;
                            el.childNodes.forEach(node=>{
                                if(node.nodeType===3){
                                    const content=node.textContent;
                                    if(content.toLowerCase().includes(term)){
                                        const replaced=content.replace(new RegExp(term,"gi"),match=>"<span class=\'highlight-search\'>"+match+"</span>");
                                        const temp=document.createElement("div");
                                        temp.innerHTML=replaced;
                                        while(temp.firstChild){el.insertBefore(temp.firstChild,node);}
                                        el.removeChild(node);
                                    }
                                } else highlight(node);
                            });
                        }
                        highlight(container.querySelector(".dd-content"));
                    });
                });
            });
            </script>
            </head><body><h1 style="color:#f92672;">Debug Dump</h1>';
        }

        // use the caller frame if available so file/line point to where dd() was invoked
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[1] ?? $trace[0] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        foreach($vars as $i=>$var){
            $varName="Variable #".($i+1);
            $type=getHumanReadableType($var);

            if(!$isCli){
                $safeFileLine = htmlspecialchars("{$file}:{$line}", ENT_QUOTES);
                echo "<div class='dd-container'>";
                echo "<div class='dd-header'><span>{$varName} ({$type}) - {$safeFileLine}</span><div class='dd-controls'>
                <button class='expand-all'>Expand All</button>
                <button class='collapse-all'>Collapse All</button>
                </div></div>";
                echo "<div class='search-container'><form class='search-form'><input type='text' class='search-input' placeholder='Search...'><button type='submit' class='search-btn'>Search</button></form></div>";
                // use a div.dump with white-space: pre so inner HTML remains interactive
                echo "<div class='dd-content'><div class='dump'>".formatVar($var)."</div></div>";
                echo "</div>";
            } else {
                echo "\n\033[1;36m{$varName} ({$type})\033[0m\n";
                var_dump($var);
                echo "\033[0;90mCalled from: {$file}:{$line}\033[0m\n";
            }
        }

        if(!$isCli) echo "</body></html>";
        exit(1);
    }
}

function getHumanReadableType(mixed $var): string {
    return match(true){
        is_null($var)=>'null',
        is_array($var)=>'array:'.count($var),
        is_object($var)=>'object:'.get_class($var),
        is_bool($var)=>'boolean:'.($var?'true':'false'),
        is_string($var)=>'string:'.strlen($var),
        is_int($var)=>'int',
        is_float($var)=>'float',
        is_resource($var)=>'resource:'.get_resource_type($var),
        default=>gettype($var)
    };
}

function formatVar(mixed $var, int $depth=0, int $maxDepth=10, &$seen=[]): string {
    if ($depth > $maxDepth) return '<span class="null">*MAX DEPTH*</span>';

    if (is_null($var)) return '<span class="null">null</span>';
    if (is_bool($var)) return '<span class="boolean">' . ($var ? 'true' : 'false') . '</span>';
    if (is_string($var)) return '<span class="string">"' . htmlspecialchars($var) . '"</span>';
    if (is_int($var) || is_float($var)) return '<span class="number">' . $var . '</span>';

    if (is_array($var)) {
        // use a stable id for arrays by casting to object (acceptable for detection of recursion here)
        $id = spl_object_id((object)$var);
        if (isset($seen[$id])) return '<span class="null">*RECURSION*</span>';
        $seen[$id] = true;

        $count = count($var);
        $preview = array_slice($var, 0, 3, true);
        $previewText = implode(', ', array_map(fn($k) => is_string($k) ? '"' . $k . '"' : $k, array_keys($preview)));
        if ($count > 3) $previewText .= ', ...';
        $html = '<span class="collapsible expanded"><span class="collapse-toggle"></span><span class="array">array:' . $count . ' [' . $previewText . ']</span><span class="collapse-content">';
        foreach ($var as $k => $v) {
            $html .= "\n" . str_repeat('  ', $depth + 1) . '<span class="property">' . htmlspecialchars((string)$k) . '</span> => ' . formatVar($v, $depth + 1, $maxDepth, $seen);
        }
        $html .= '</span>]</span>';
        return $html;
    }

    if (is_object($var)) {
        $id = spl_object_id($var);
        if (isset($seen[$id])) return '<span class="null">*RECURSION*</span>';
        $seen[$id] = true;

        if ($var instanceof Closure) {
            $ref = new ReflectionFunction($var);
            $params = array_map(fn($p) => '$' . $p->getName(), $ref->getParameters());
            $file = $ref->getFileName() ?? 'unknown';
            $start = $ref->getStartLine();
            $end = $ref->getEndLine();
            return '<span class="object">Closure(' . implode(', ', $params) . ') @ ' . $file . ':' . $start . '-' . $end . '</span>';
        }

        $class = get_class($var);
        $props = (new ReflectionObject($var))->getProperties();
        $preview = array_slice($props, 0, 3);
        $previewText = implode(', ', array_map(fn($p) => $p->getName(), $preview));
        if (count($props) > 3) $previewText .= ', ...';
        $html = '<span class="collapsible expanded"><span class="collapse-toggle"></span><span class="object">' . $class . ' {' . $previewText . '}</span><span class="collapse-content">';
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $html .= "\n" . str_repeat('  ', $depth + 1) . '<span class="property">' . $prop->getName() . '</span> => ' . formatVar($prop->getValue($var), $depth + 1, $maxDepth, $seen);
        }
        $html .= '</span>}</span>';
        return $html;
    }

    return htmlspecialchars(var_export($var, true));
}
