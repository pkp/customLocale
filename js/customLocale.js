var customLocalesApp = {};
$(function() {
	customLocalesApp = {
		el: '#customLocales',
		data: {
			edited: {},
			name: '',
			localEdited: {}, // temporarily holds edited values, both saved and new
			localeKeysMaster: [], // master list of all keys in locale file
			filteredKeysList: [], // filtered keys allows for paginating search results
			currentLocaleKeys: [], // keys available on a given page
			searchPhrase: '',
			phraseSearched: '',
			onlyModified: false,
			currentPage: 0,
			itemsPerPage: 25,
			showAdjacentPages: 1,
			displaySearchResults: false,
		},
		methods: {
			search: function() {
				if (!this.searchPhrase && !this.onlyModified) {
					this.initializeView();
					return;
				}
				this.phraseSearched = this.searchPhrase;
				this.displaySearchResults = true;
				this.currentPage = -1;
				var filteredKeysList = [];
				var search = new RegExp(this.searchPhrase, 'i');
				for (var i = -1; ++i < this.localeKeysMaster.length; ) {
					var item = {
						localeKey: this.localeKeysMaster[i].localeKey,
						value: this.localeKeysMaster[i].value,
					};
					var edited = this.localEdited[item.localeKey];
					var canAdd = !this.onlyModified || edited;
					if (canAdd && search.test(item.localeKey + item.value + (edited || ''))) {
						filteredKeysList.push(item);
					}
				}
				// Similar to initializeView, but uses search results rather than master list
				this.currentPage = 1;
				var end = this.currentPage * this.itemsPerPage;
				var start = end - this.itemsPerPage;
				this.filteredKeysList = filteredKeysList;
				this.currentLocaleKeys = this.filteredKeysList.slice(start, end);
			},
			initializeView: function() {
				this.searchPhrase = '';
				this.phraseSearched = '';
				this.currentPage = 1;
				this.displaySearchResults = false;
				this.filteredKeysList = this.localeKeysMaster; // Filtering changes
				var end = this.currentPage * this.itemsPerPage;
				var start = end - this.itemsPerPage;
				this.currentLocaleKeys = this.filteredKeysList.slice(start, end);
			}
		},
		computed: {
			pages() {
				var items = [];
				var innerMax = Math.min(
					this.currentPage + this.showAdjacentPages,
					this.lastPage
				);
				var innerMin = Math.max(this.currentPage - this.showAdjacentPages, 1);
	
				// Ensure there are always enough inner links
				// If the current page is at the start or end, expand the min/max
				if (innerMax - innerMin < this.showAdjacentPages) {
					const remainder = this.showAdjacentPages - (innerMax - innerMin);
					if (innerMin === 1) {
						innerMax = Math.min(innerMax + remainder, this.lastPage);
					} else if (innerMax === this.lastPage) {
						innerMin = Math.max(innerMin - remainder, 1);
					}
				}
	
				// Add the starting page
				if (innerMin > 1) {
					items.push({
						value: 1
					});
				}
	
				// Add a separator between the starting page and the inner pages
				if (innerMin > 2) {
					items.push({
						isSeparator: true
					});
				}
	
				for (var i = innerMin; i <= innerMax; i++) {
					items.push({
						value: i,
						isCurrent: this.currentPage === i
					});
				}
	
				// Add a separator between the last page and the inner pages
				if (innerMax < this.lastPage - 1) {
					items.push({
						isSeparator: true
					});
				}
	
				// Add the last page
				if (innerMax < this.lastPage) {
					items.push({
						value: this.lastPage
					});
				}
	
				return items;
			},
			lastPage: function() {
				if (!this.filteredKeysList.length) {
					return 0;
				}
				return Math.ceil(this.filteredKeysList.length / this.itemsPerPage);
			}
		},
		watch: {
			currentPage: function(newVal, oldVal) {
				if (newVal === oldVal) {
					return;
				}
				var end = newVal * this.itemsPerPage;
				var start = end - this.itemsPerPage;
				this.currentLocaleKeys = this.filteredKeysList.slice(start, end); // Filtering changes
			}
		},
		mounted: function() {
			this.initializeView();
			// merges edited into localEdited
			this.localEdited = {}
			for (var attrName in this.edited) {
				this.localEdited[attrName] = this.edited[attrName];
			}
		},
	};
});