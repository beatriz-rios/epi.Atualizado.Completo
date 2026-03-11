function entrar() {
    window.location.href = "dashboard.html";
}    
function animarLogin(event) {
    event.preventDefault();
    document.getElementById("loginContainer").classList.add("slide-up");

    setTimeout(() => {
        event.target.submit();
    }, 400);
}
