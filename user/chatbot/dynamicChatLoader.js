document.addEventListener("DOMContentLoaded", function() {
    let script1 = document.createElement("script");
    script1.src = "https://cdn.jsdelivr.net/gh/ejjays/mvj/bot-initialize.js";
    document.head.appendChild(script1);

    let script2 = document.createElement("script");
    script2.src = "https://tars-file-upload.s3.amazonaws.com/bulb/js/widget.js";
    document.head.appendChild(script2);
});