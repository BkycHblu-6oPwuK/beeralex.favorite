// пример работы с модулем избранного из разных файлов

//api
export const toggleFavourite = async (productId) => {
    const formData = new URLSearchParams();
    formData.append('sessid', bxSessid);
    formData.append('productId', productId);
    
    const response = await fetch(`/bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.toggle&productID=${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error(`Ошибка HTTP: ${response.status}`);
    }

    const result = await response.json();
    
    return result;
}

export const getFavouriteIds = async () => {
    const formData = new URLSearchParams();
    formData.append('sessid', bxSessid);
    
    const response = await fetch(`/bitrix/services/main/ajax.php?action=beeralex:favorite.FavoriteController.get`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error(`Ошибка HTTP: ${response.status}`);
    }

    const result = await response.json();

    return result;
}
// api end

// vuex store

import { createStore } from 'vuex';
import favouriteModule from './modules/favourite.js';

const store = createStore({
    modules: {
        favourite: favouriteModule,
    },
});

store.dispatch('favourite/initialize');

export default store;

//vuex store end

// module favourite

import { getFavouriteIds, toggleFavourite } from '../../../api/catalog';

const favouriteModule = {
    namespaced: true,
    state: {
        favourite: [], // массив с id товаров пользователя
    },
    mutations: {
        setFavourite(state, favourite) {
            state.favourite = favourite;
        },
        addFavourite(state, productId) {
            state.favourite = [...state.favourite, productId];
        },
        removeFavourite(state, productId) {
            state.favourite = state.favourite.filter((item) => item !== productId);
        },
    },
    actions: {
        async initialize({ commit }) {
            const result = await getFavouriteIds();
            commit('setFavourite', result.data);
        },
        async toggleFavourite({ commit }, productId) {
            const result = await toggleFavourite(productId);
            if (result.data.action === 'add') {
                commit('addFavourite', productId);
            } else {
                commit('removeFavourite', productId);
            }
        },
    },
    getters: {
        getFavourite: (state) => state.favourite,
        getFavouriteCount: (state) => state.favourite.length,
    },
};

export default favouriteModule;

// module favourite end

// vue component favouritesSmall
/*
<script setup>
import { ref, watch } from 'vue';
import store from '../../store/about';
const props = defineProps({ // компонент принимает ид товара, и при клике на кнопку этот товар будет добавляться
    productId: {
        type: Number,
        required: true
    }
})
const productId = Number(props.productId);
const isFavourite = ref(false);
watch(() => store.getters['favourite/getFavourite'], (newValue) => {
    isFavourite.value = newValue.includes(productId);
})
const toggle = () => store.dispatch('favourite/toggleFavourite', productId);
</script>

<template>
    <div @click.prevent="toggle" class="product-card__image-favourites-icon" :class="{ 'product-card__icon-favourites_active': isFavourite }">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M14 7.00019C11.9007 4.55366 8.39273 3.79757 5.76243 6.03785C3.13213 8.27813 2.76182 12.0238 4.82741 14.6734C6.54481 16.8763 11.7423 21.5225 13.4457 23.0263C13.6363 23.1945 13.7316 23.2787 13.8427 23.3117C13.9397 23.3406 14.0459 23.3406 14.1429 23.3117C14.254 23.2787 14.3493 23.1945 14.5399 23.0263C16.2434 21.5225 21.4408 16.8763 23.1582 14.6734C25.2238 12.0238 24.8987 8.25457 22.2232 6.03785C19.5477 3.82114 16.0993 4.55366 14 7.00019Z"
                stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" />
        </svg>
    </div>
</template>
*/
// vue component end

// vue app

import { createApp } from "vue";
import favouritesSmall from "../../common/components/favouritesSmall.vue";
window.vueApps = {
    ...(window.vueApps ?? {}),
    createFavouriteSmall(productId) {
        const app = createApp({
            template: `<favouritesSmall :productId="${productId}"/>`
        });
        app.component('favouritesSmall', favouritesSmall);
        return app;
    }
}
//vue app end

// js - создание vue приложений на карточках 

const productCard = document.querySelectorAll('.product-card-about');
productCard && productCard.forEach((element) => {
    const productId = element.dataset.id; // дата атрибут в котором ид товара
    const favouriteButton = element.querySelector('.vue-favourites');
    if (favouriteButton) {
        window.vueApps.createFavouriteSmall(productId).mount(favouriteButton)
    }
})
// js end