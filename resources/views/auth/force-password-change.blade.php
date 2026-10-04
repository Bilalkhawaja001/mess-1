<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0;min-height:100vh;font-family:Arial,sans-serif;background:linear-gradient(135deg,#0f172a,#0369a1);display:flex;align-items:center;justify-content:center}.card{width:100%;max-width:420px;background:#fff;border-radius:18px;padding:28px;box-shadow:0 20px 50px rgba(0,0,0,.25)}h2{margin:0 0 8px;color:#0f172a}p{margin:0 0 22px;color:#64748b;font-size:14px}label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:7px}input{width:100%;box-sizing:border-box;padding:12px 14px;border:1px solid #cbd5e1;border-radius:12px;margin-bottom:14px;font-size:15px}button{width:100%;border:0;border-radius:12px;padding:13px;background:#0284c7;color:#fff;font-weight:800;cursor:pointer;font-size:15px}.error{background:#fef2f2;color:#991b1b;padding:10px 12px;border-radius:10px;font-size:13px;margin-bottom:14px}.success{background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:10px;font-size:13px;margin-bottom:14px}.logout{margin-top:14px;text-align:center}.logout button{background:transparent;color:#64748b;padding:0;font-weight:700}
    </style>
</head>
<body>
<div class="card">
    <h2>Change Your Password</h2>
    <p>For security, please set a new password before continuing.</p>
    @if ($errors->any())<div class="error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div id="passwordMessage" aria-live="polite"></div>
    <form method="POST" action="{{ route('password.force.update') }}" id="forcePasswordForm" data-api-action="{{ route('api.member.change-password') }}">
        @csrf
        <label>New Password</label>
        <input type="password" name="new_password" minlength="6" required autocomplete="new-password">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
        <button type="submit" id="forcePasswordButton" data-default-text="Update Password">Update Password</button>
    </form>
    <form class="logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
</div>
<script>
(() => {
 const form=document.getElementById('forcePasswordForm'), button=document.getElementById('forcePasswordButton'), message=document.getElementById('passwordMessage'); let pending=false;
 const showMessage=(text,type='error')=>{message.className=type;message.textContent=text;};
 form.addEventListener('submit',async(event)=>{event.preventDefault(); if(pending) return; const formData=new FormData(form); const password=String(formData.get('new_password')||''); const confirmPassword=String(formData.get('confirm_password')||'');
  if(password.length<6){showMessage('Password must be at least 6 characters.');return;} if(password!==confirmPassword){showMessage('Confirm password must match new password.');return;}
  pending=true; button.disabled=true; button.textContent='Updating...'; message.className=''; message.textContent='';
  try { const response=await fetch(form.dataset.apiAction,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':String(formData.get('_token')||'')},body:formData,credentials:'same-origin'}); const data=await response.json().catch(()=>({}));
   if(!response.ok){const errors=data.errors||{}; const firstError=Object.values(errors).flat()[0]; showMessage(firstError||data.message||'Password update failed.'); return;}
   showMessage(data.message||'Password changed successfully.','success'); window.location.href=data.redirect||'/';
  } catch(e) { showMessage('Password update failed. Please try again.'); }
  finally { pending=false; button.disabled=false; button.textContent=button.dataset.defaultText; }
 });
})();
</script>
</body>
</html>
