document.addEventListener("DOMContentLoaded", function () {
	const coinInput = document.getElementById("coin");
	const amountInput = document.getElementById("amount");
	const suggestionsBox = document.getElementById("suggestions");
	const tradeForm = document.getElementById("trade-form");
	const successModalElement = document.getElementById('successModal');

	fetchCryptoData();
	fetchBalance();

	coinInput.addEventListener("input", function () {
		const searchQuery = coinInput.value.toLowerCase();
		if (searchQuery) {
			filterCoins(searchQuery);
		} else {
			suggestionsBox.innerHTML = '';
		}
		updateTotalPrice();
	});

	amountInput.addEventListener("input", function () {
		updateTotalPrice();
	});

	tradeForm.addEventListener("submit", function (e) {
		e.preventDefault();
		executeTrade();
	});

	if (successModalElement) {
		successModalElement.addEventListener('hidden.bs.modal', function () {
			window.location.reload();
		});
	}
});

let coinsData = [];
let userBalance = 0;

function fetchCryptoData() {
	fetch("http://localhost/dbCryptoCall.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/json"
		},
		body: JSON.stringify({
			action: "getTop100Crypto"
		})
	})
	.then(response => response.json())
	.then(data => {
		coinsData = data.data;
	})
	.catch(error => console.error("Error fetching data:", error));
}

function filterCoins(query) {
	const filteredCoins = coinsData.filter(coin =>
		coin.name.toLowerCase().includes(query) || coin.symbol.toLowerCase().includes(query)
	);
	displaySuggestions(filteredCoins);
}

function displaySuggestions(coins) {
	const suggestionsBox = document.getElementById("suggestions");
	suggestionsBox.innerHTML = '';

	if (coins.length === 0) {
		suggestionsBox.innerHTML = '<div>No coins found</div>';
		return;
	}

	coins.forEach(coin => {
		const suggestionItem = document.createElement("div");
		suggestionItem.classList.add("suggestion-item");
		suggestionItem.textContent = `${coin.name} (${coin.symbol})`;
		suggestionItem.addEventListener("click", function () {
			selectCoin(coin);
		});
		suggestionsBox.appendChild(suggestionItem);
	});
}

function selectCoin(coin) {
	const coinInput = document.getElementById("coin");
	coinInput.value = `${coin.name} (${coin.symbol})`;
	document.getElementById("suggestions").innerHTML = '';
	updateTotalPrice();
}

function updateTotalPrice() {
	const coinInput = document.getElementById("coin");
	const amountInput = document.getElementById("amount");
	const tradeType = document.querySelector('input[name="trade-type"]:checked').value;

	const coinDetails = coinInput.value.split(' ');
	const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');

	if (!coinSymbol) return;

	const amount = parseFloat(amountInput.value);
	const pricePerUnit = getCoinPrice(coinSymbol);

	const totalPrice = amount * pricePerUnit;

	document.getElementById("total-price").textContent = `$${totalPrice.toFixed(2)}`;
}

function getCoinPrice(symbol) {
	const coin = coinsData.find(coin => coin.symbol === symbol);
	return coin ? parseFloat(coin.priceUsd) : 0;
}

function fetchBalance() {
	fetch('get_balance.php')
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				userBalance = parseFloat(data.balance);
				console.log("User Balance:", userBalance);
			} else {
				console.error('Error fetching balance:', data.message);
			}
		})
		.catch(error => {
			console.error('Error fetching balance:', error);
		});
}

function checkBuyAvailability(totalPrice, coinSymbol, coinName, amount, coinPrice) {
	if (totalPrice > userBalance) {
		const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
		document.getElementById('errorMessage').textContent = `You do not have enough funds to complete this purchase. Your balance is $${userBalance.toFixed(2)}.`;
		errorModal.show();
	} else {
		fetch('trade.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				action: 'buy',
				coin_symbol: coinSymbol,
				coin_name: coinName,
				amount: amount,
				balance: userBalance - totalPrice,
				price: coinPrice
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				const successModal = new bootstrap.Modal(document.getElementById('successModal'));
				successModal.show();
			} else {
				const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
				document.getElementById('errorMessage').textContent = data.message || 'There was an error processing your buy order.';
				errorModal.show();
			}
		})
		.catch(error => {
			const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
			document.getElementById('errorMessage').textContent = 'Network error: ' + error.message;
			errorModal.show();
		});
	}
}

function checkSellAvailability(amount, coinSymbol, coinName, coinPrice) {
	fetch('get_portfolio.php')
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				const portfolio = data.portfolio;
				const coin = portfolio.find(item => item.coin_symbol === coinSymbol);
				if (!coin || coin.quantity < amount) {
					const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
					document.getElementById('errorMessage').textContent = "You do not have enough of this coin to sell.";
					errorModal.show();
				} else {
					executeSellTrade(amount, coinSymbol, coinName, coinPrice);
				}
			} else {
				console.error('Error fetching portfolio:', data.message);
				const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
				document.getElementById('errorMessage').textContent = data.message || 'Error fetching portfolio.';
				errorModal.show();
			}
		})
		.catch(error => {
			console.error('Fetch error (portfolio):', error);
			const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
			document.getElementById('errorMessage').textContent = 'Network error fetching portfolio.';
			errorModal.show();
		});
}

function executeSellTrade(amount, coinSymbol, coinName, coinPrice) {
	const totalPrice = amount * coinPrice;
	fetch('trade.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify({
			action: 'sell',
			coin_symbol: coinSymbol,
			coin_name: coinName,
			amount: amount,
			price: coinPrice,
			profit: totalPrice
		})
	})
	.then(response => response.json())
	.then(data => {
		if(data.status === 'success'){
			const successModal = new bootstrap.Modal(document.getElementById('successModal'));
			successModal.show();
		} else {
			const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
			document.getElementById('errorMessage').textContent = data.message || "There was an error processing your sell order";
			errorModal.show();
		}
	})
	.catch(error => {
		console.error("Error executing sell:", error);
		const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
		document.getElementById('errorMessage').textContent = 'Network error executing sell.';
		errorModal.show();
	});
}

function executeTrade() {
	const coinInput = document.getElementById("coin");
	const amountInput = document.getElementById("amount");
	const tradeType = document.querySelector('input[name="trade-type"]:checked').value;

	const coinDetails = coinInput.value.split(' ');
	const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');
	const coinName = coinDetails[0];
	const amount = parseFloat(amountInput.value);
	const coinPrice = getCoinPrice(coinSymbol);

	if (tradeType === 'buy') {
		checkBuyAvailability(amount * coinPrice, coinSymbol, coinName, amount, coinPrice);
	} else if (tradeType === 'sell') {
		checkSellAvailability(amount, coinSymbol, coinName, coinPrice);
	}
}



