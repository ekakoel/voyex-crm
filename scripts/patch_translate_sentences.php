<?php
$targets=['zh_Hant'=>'lang/zh_Hant/ui_core.php','zh_Hans'=>'lang/zh_Hans/ui_core.php'];
$phrase=[
'zh_Hant'=>[
'Enter your registered email address, and we will send a secure password reset link.'=>'請輸入你註冊的電子郵件，我們會寄送安全的密碼重設連結。',
'Input your registered email address.'=>'輸入你註冊的電子郵件。',
'Open the reset link sent to your inbox.'=>'開啟寄到你信箱的重設連結。',
'Use a strong password to keep your account secure and easy to recover in the future.'=>'請使用高強度密碼，確保帳號安全且日後容易復原。',
'If your email is registered, a password reset link has been sent.'=>'若你的電子郵件已註冊，重設密碼連結已寄出。',
'Please wait before retrying.'=>'請稍候再重試。',
'Use the same email address registered in the system.'=>'請使用系統中已註冊的電子郵件。',
'Please confirm your email and enter your new password.'=>'請確認你的電子郵件並輸入新密碼。',
'Use your registered account credentials.'=>'請使用你註冊的帳號憑證。',
'Sign in to continue managing your travel CRM workflow in one secure place.'=>'登入以在同一個安全平台持續管理你的旅遊 CRM 流程。',
'Monitor inquiry, quotation, booking, and invoice flow in one dashboard.'=>'在單一儀表板監控詢價、報價、預訂與發票流程。',
'Role-based access keeps each team focused on the right tasks.'=>'角色權限可讓各團隊聚焦正確任務。',
'Activity and status tracking help reduce manual follow-up errors.'=>'活動與狀態追蹤有助於降低人工跟進錯誤。',
'gallery full image'=>'圖庫完整圖片',' image'=>' 圖片',' thumbnail '=>' 縮圖 ',
'password and sign in again securely.'=>'密碼，並安全地重新登入。',
'KPI'=>'KPI','CRM'=>'CRM','PDF'=>'PDF'
],
'zh_Hans'=>[
'Enter your registered email address, and we will send a secure password reset link.'=>'请输入你注册的电子邮箱，我们会发送安全的密码重置链接。',
'Input your registered email address.'=>'输入你注册的电子邮箱。',
'Open the reset link sent to your inbox.'=>'打开发送到你邮箱的重置链接。',
'Use a strong password to keep your account secure and easy to recover in the future.'=>'请使用高强度密码，确保账号安全且便于日后恢复。',
'If your email is registered, a password reset link has been sent.'=>'如果你的电子邮箱已注册，重置密码链接已发送。',
'Please wait before retrying.'=>'请稍后再重试。',
'Use the same email address registered in the system.'=>'请使用系统中已注册的电子邮箱。',
'Please confirm your email and enter your new password.'=>'请确认你的电子邮箱并输入新密码。',
'Use your registered account credentials.'=>'请使用你注册的账号凭证。',
'Sign in to continue managing your travel CRM workflow in one secure place.'=>'登录以在同一安全平台持续管理你的旅游 CRM 流程。',
'Monitor inquiry, quotation, booking, and invoice flow in one dashboard.'=>'在单一仪表板监控询价、报价、预订与发票流程。',
'Role-based access keeps each team focused on the right tasks.'=>'基于角色的访问可让各团队专注正确任务。',
'Activity and status tracking help reduce manual follow-up errors.'=>'活动与状态追踪有助于减少人工跟进错误。',
'gallery full image'=>'图库完整图片',' image'=>' 图片',' thumbnail '=>' 缩略图 ',
'password and sign in again securely.'=>'密码，并安全地重新登录。',
'KPI'=>'KPI','CRM'=>'CRM','PDF'=>'PDF'
]
];

function exportArr(array $a){$o="<?php\n\nreturn [\n";foreach($a as $k=>$v){$k=str_replace(["\\","'"],["\\\\","\\'"],$k);if(is_string($v)){$v=str_replace(["\\","'"],["\\\\","\\'"],$v);$o.="    '{$k}' => '{$v}',\n";}else{$o.="    '{$k}' => ".var_export($v,true).",\n";}}return $o."];\n";}

foreach($targets as $lc=>$f){$arr=include $f;$c=0;foreach($arr as $k=>$v){if(!is_string($v)||$v==='')continue;$nv=$v;foreach($phrase[$lc] as $from=>$to){$nv=str_replace($from,$to,$nv);}if($nv!==$v){$arr[$k]=$nv;$c++;}}file_put_contents($f,exportArr($arr));echo "$lc phrase2=$c\n";}

