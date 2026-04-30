$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open('c:\laragon\www\P.colegios-v1.2\docs\ap-ai-rg-134_acta_de_reintegro-v2.doc')
$text = $doc.Content.Text
$doc.Close()
$word.Quit()
$text | Out-File -FilePath c:\laragon\www\P.colegios-v1.2\docs\acta_reintegro.txt
