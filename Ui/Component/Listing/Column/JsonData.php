<?php
/**
 * Orangecat Verifactuapi JSON Data Column
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class JsonData extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $jsonData = $item[$fieldName];
                    
                    // Format JSON for display
                    $decodedData = json_decode($jsonData, true);
                    $prettyJson = json_encode($decodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    
                    // Truncate for grid display
                    $truncated = mb_strlen($jsonData) > 100 ? mb_substr($jsonData, 0, 100) . '...' : $jsonData;
                    
                    // Create unique ID for this cell
                    $uniqueId = 'json_' . $fieldName . '_' . $item['log_id'];
                    
                    // HTML with modal trigger
                    $item[$fieldName] = sprintf(
                        '<div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; color: #1979c3;" 
                              onclick="event.stopPropagation(); openJsonModal_%s()" 
                              title="Click to view full JSON">%s</div>
                        <script>
                        if (!window.openJsonModal_%s) {
                            window.openJsonModal_%s = function() {
                                var modal = document.createElement("div");
                                modal.style.cssText = "position:fixed;top:0;left:0;width:100%%;height:100%%;background:rgba(0,0,0,0.8);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;";
                                modal.onclick = function() { document.body.removeChild(modal); };
                                
                                var content = document.createElement("div");
                                content.style.cssText = "background:white;border-radius:8px;padding:20px;max-width:90%%;max-height:90%%;overflow:auto;position:relative;";
                                content.onclick = function(e) { e.stopPropagation(); };
                                
                                var closeBtn = document.createElement("button");
                                closeBtn.innerHTML = "×";
                                closeBtn.style.cssText = "position:absolute;top:10px;right:10px;font-size:24px;border:none;background:transparent;cursor:pointer;color:#666;";
                                closeBtn.onclick = function() { document.body.removeChild(modal); };
                                
                                var copyBtn = document.createElement("button");
                                copyBtn.innerHTML = "Copy to Clipboard";
                                copyBtn.style.cssText = "margin-bottom:10px;padding:8px 16px;background:#1979c3;color:white;border:none;border-radius:4px;cursor:pointer;";
                                copyBtn.onclick = function() {
                                    var textarea = document.createElement("textarea");
                                    textarea.value = %s;
                                    document.body.appendChild(textarea);
                                    textarea.select();
                                    document.execCommand("copy");
                                    document.body.removeChild(textarea);
                                    copyBtn.innerHTML = "Copied!";
                                    setTimeout(function() { copyBtn.innerHTML = "Copy to Clipboard"; }, 2000);
                                };
                                
                                var pre = document.createElement("pre");
                                pre.style.cssText = "background:#f5f5f5;padding:15px;border-radius:4px;overflow:auto;max-height:70vh;margin:0;font-family:monospace;font-size:12px;";
                                pre.textContent = %s;
                                
                                content.appendChild(closeBtn);
                                content.appendChild(copyBtn);
                                content.appendChild(pre);
                                modal.appendChild(content);
                                document.body.appendChild(modal);
                            };
                        }
                        </script>',
                        $uniqueId,
                        htmlspecialchars($truncated, ENT_QUOTES, 'UTF-8'),
                        $uniqueId,
                        $uniqueId,
                        json_encode($prettyJson),
                        json_encode($prettyJson)
                    );
                } else {
                    $item[$fieldName] = '<span style="color:#999;">—</span>';
                }
            }
        }
        
        return $dataSource;
    }
}
