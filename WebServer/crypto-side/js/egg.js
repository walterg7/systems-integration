(function() {
    const secretCode = ".yay";
    let inputBuffer = "";
    let confettiContainer;

    function createConfetti() {
        confettiContainer = document.createElement('div');
        confettiContainer.style.position = 'fixed';
        confettiContainer.style.top = '0';
        confettiContainer.style.left = '0';
        confettiContainer.style.width = '100%';
        confettiContainer.style.height = '100%';
        confettiContainer.style.pointerEvents = 'none';
        document.body.appendChild(confettiContainer);

        const colors = ['#f00', '#0f0', '#00f', '#ff0', '#f0f', '#0ff'];
        const numConfetti = 100;

        for (let i = 0; i < numConfetti; i++) {
            const confetti = document.createElement('div');
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.borderRadius = '50%';
            confetti.style.position = 'absolute';
            confetti.style.left = `${Math.random() * 100}vw`;
            confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear infinite`;
            confetti.style.opacity = Math.random();
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
            confettiContainer.appendChild(confetti);
        }
    }

    document.addEventListener('keypress', function(event) {
        const key = event.key;
        inputBuffer += key;
        if (inputBuffer.length > secretCode.length) {
            inputBuffer = inputBuffer.slice(-secretCode.length);
        }

        if (inputBuffer === secretCode) {
            triggerParty();
            inputBuffer = "";
        }
    });

    function triggerParty() {
        if (!confettiContainer) {
            createConfetti();
        } else {
            
        }
    }

    
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = `@keyframes fall {
        0% { transform: translateY(-20px) rotate(0deg) scale(1); opacity: 0; }
        50% { opacity: 1; }
        100% { transform: translateY(110vh) rotate(360deg) scale(0.8); opacity: 0; }
    }`;
    document.head.appendChild(styleSheet);

    
    createConfetti();
    if (confettiContainer) {
        confettiContainer.style.display = 'none';
    }

    function triggerParty() {
        if (confettiContainer) {
            confettiContainer.style.display = 'block';
            
            setTimeout(() => {
                if (confettiContainer) {
                    confettiContainer.style.display = 'none';
                }
            }, 5000); 
        }
    }
})();


