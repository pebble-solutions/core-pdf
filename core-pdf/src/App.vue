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
				<AppMenuItem :href="'/operation/' + operation.id" icon="bi bi-file-earmark"
					v-for="operation in filteredOperations" :key="operation.id">
					{{ operation.fichier }}
				</AppMenuItem>
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
import AppWrapper from '@/components/pebble-ui/AppWrapper.vue';
import AppMenu from '@/components/pebble-ui/AppMenu.vue';
import AppMenuItem from '@/components/pebble-ui/AppMenuItem.vue';
import { mapActions, mapState } from 'vuex';
import CONFIG from "@/config.json";

export default {
	data() {
		return {
			cfg: CONFIG.cfg,
			cfgMenu: CONFIG.cfgMenu,
			cfgSlots: CONFIG.cfgSlots,
			pending: {
				elements: true
			},
			isConnectedUser: false,
			displaySearch: ''
		};
	},
	computed: {
		...mapState(['operations', 'elements', 'openedElement']),
		filteredOperations() {
			if (this.operations.length !== 0) {
				if (this.displaySearch !== '') {
					return this.operations.filter((item) => {
						return item.fichier.includes(this.displaySearch);
					});
				} else {
					return this.operations;
				}
			} else {
				this.loadData();
				return [];
			}
		},
	},
	watch: {
		$route() {
			if (this.$route.name !== 'Home') {
				this.$app.dispatchEvent('menuChanged', 'list');
			}
		}
	},
	methods: {
		setLocal_user(user) {
			if (user) {
				this.$store.dispatch('login', user);
				this.isConnectedUser = true;
			} else {
				this.$store.dispatch('logout');
				this.isConnectedUser = false;
			}
		},
		switchStructure(structureId) {
			this.$router.push('/');
			this.$store.dispatch('switchStructure', structureId);
		},
		...mapActions(['refreshOperations', 'closeElement']),
		loadData() {
			axios.get('http://172.18.0.3/public/liste-operations')
				.then(response => {
					this.$store.dispatch('refreshOperations', response.data);
				})
				.catch(error => {
					console.error(error);
				});
		},
		includeInRoute(pathName) {
			if (pathName && this.$route.fullPath) {
				let routeName = this.$route.fullPath;
				return routeName.includes(pathName);
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
  