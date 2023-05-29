<template>
	<AppWrapper :cfg="cfg" :cfg-menu="cfgMenu" :cfg-slots="cfgSlots" @auth-change="setLocal_user"
		@structure-change="switchStructure">

		<template v-slot:header>
			<div class="mx-2 d-flex align-items-center" v-if="openedElement">
				<router-link to="/" custom v-slot="{ navigate, href }">
					<a class="btn btn-dark me-2" :href="href" @click="navigate">
						<i class="bi bi-arrow-left"></i>
					</a>
				</router-link>
				<router-link :to="'/element/' + openedElement.id + '/properties'" custom v-slot="{ navigate, href }">
					<a class="btn btn-dark me-2" :href="href" @click="navigate">
						<i class="bi bi-file-earmark me-1"></i>
						{{ openedElement.name }}
					</a>
				</router-link>

				<div class="dropdown">
					<button class="btn btn-dark dropdown-toggle" type="button" id="fileDdMenu" data-bs-toggle="dropdown"
						aria-expanded="false">
						Fichier
					</button>
					<ul class="dropdown-menu" aria-labelledby="fileDdMenu">
						<li>
							<router-link :to="'/element/' + openedElement.id + '/informations'" custom
								v-slot="{ navigate, href }">
								<a class="dropdown-item" :href="href" @click="navigate">Informations</a>
							</router-link>
						</li>
					</ul>
				</div>
			</div>
		</template>


		<template v-slot:menu>
			<AppMenu>
				<AppMenuItem href="/" look="dark" icon="bi bi-house">Accueil</AppMenuItem>
				<AppMenuItem href="/about" look="dark" icon="bi bi-app">À propos</AppMenuItem>
				<AppMenuItem href="/operation" look="dark" icon="bi bi-app">Liste des opérations</AppMenuItem>
			</AppMenu>
		</template>

		<template v-slot:list>
			<AppMenu v-if="includeInRoute('operation')">
				<input type="text" class="form-control my-2 px-2" placeholder="Rechercher..." v-model="displaySearch">
				<AppMenuItem :href="'/operation/'+operation.id" icon="bi bi-file-earmark" v-for="operation in resultSearch()"
					:key="operation.id">{{ operation.fichier }}</AppMenuItem>
			</AppMenu>
		</template>

		<template v-slot:core v-if="isConnectedUser">
			<div class="px-2 bg-light">
				<router-view :cfg="cfg" />
			</div>
		</template>

	</AppWrapper>
</template>

<script>

import axios from 'axios';
import AppWrapper from '@/components/pebble-ui/AppWrapper.vue'
import AppMenu from '@/components/pebble-ui/AppMenu.vue'
import AppMenuItem from '@/components/pebble-ui/AppMenuItem.vue'
import { mapActions, mapState } from 'vuex'


import CONFIG from "@/config.json"


export default {

	data() {
		return {
			cfg: CONFIG.cfg,
			cfgMenu: CONFIG.cfgMenu,
			cfgSlots: CONFIG.cfgSlots,
			pending: {
				elements: true
			},
			isConnectedUser: false
		}
	},

	computed: {
		...mapState(['operations','elements', 'openedElement']),
	},

	watch: {

		/**
		 * Surveille le chemin et selon l'evenement "menuChanged",
		 * il affiche la liste associé au menu 
		 */
		$route() {
			if (this.$route.name !== 'Home') {
				this.$app.dispatchEvent('menuChanged', 'list');
				this.displaySearch = '';
			}
		}
	},

	methods: {

		/**
 * Retourne un tableau trié selon la recherche faite par l'utilisateur
 * 
 * @param {Array} ressource 
 * 
 * @returns {Array}
 */
		resultSearch() {
			if (this.includeInRoute('operation')) {
				if (this.operations.length != 0) {
					let operation = this.operations
					if (this.displaySearch !== '') {
						operation = operation.filter((item) => {
							return item.nom.match(this.displaySearch)
						})
					}

					return operation;
				} else if (this.isConnectedUser) {
					this.loadData()
				}
			}
		},


		/**
		 * Met à jour les informations de l'utilisateur connecté
		 * @param {Object} user Un objet LocalUser
		 */
		setLocal_user(user) {
			if (user) {
				this.$store.dispatch('login', user);
				this.isConnectedUser = true;
			}
			else {
				this.$store.dispatch('logout');
				this.isConnectedUser = false;
			}
		},

		/**
		 * Envoie une requête pour lister les éléments et les stocke dans le store
		 * 
		 * @param {Object} params Paramètre passés en GET dans l'URL
		 * @param {String} action 'update' (défaut), 'replace', 'remove'
		 */
		listElements(params, action) {
			if (this.isConnectedUser) {
				action = typeof action === 'undefined' ? 'update' : action;
				this.$app.listElements(this, params)
					.then((data) => {
						this.$store.dispatch('refreshElements', {
							action,
							elements: data
						});
					})
					.catch(this.$app.catchError);
			}
		},


		/**
		 * Change de structure, vide le store
		 * 
		 * @param {Integer} structureId
		 */
		switchStructure(structureId) {
			this.$router.push('/');
			this.$store.dispatch('switchStructure', structureId);

			if (this.isConnectedUser) {
				//this.listElements();
			}
		},


		...mapActions(['operations,closeElement']),


		loadData() {
			axios.get('http://172.18.0.3/public/liste-operations')
				.then(response => {

					this.$store.dispatch('refreshOperations', response.data);
				})
				.catch(error => {
					console.error(error);
				});
		},
		/**
		 * Retourne un bouléen en fonction de la route (true si la route contient le nom envoyer par l'utilisateur)
		 * 
		 * @param {string} pathName 
		 * 
		 * @returns {boolean}
		 */
		includeInRoute(pathName) {
			if (pathName && this.$route.fullPath) {
				let routeName = this.$route.fullPath;
				return routeName.includes(pathName)
			} else {
				return false;
			}
		},

	},




	components: {
		AppWrapper,
		AppMenu,
		AppMenuItem
	},

	mounted() {
		this.loadData();
	}

}


</script>