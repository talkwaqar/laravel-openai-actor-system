import './bootstrap';
import { createApp, h } from 'vue';

// Import Vue components
import ActorList from './components/ActorList.vue';
import ActorDetail from './components/ActorDetail.vue';
import SidebarLayout from './components/SidebarLayout.vue';
import ApiIndex from './components/ApiIndex.vue';
import PromptValidation from './components/PromptValidation.vue';
import ActorsApi from './components/ActorsApi.vue';

// Mount components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Mount Sidebar Layout if present
    const sidebarLayoutElement = document.querySelector('#sidebar-layout-app');
    if (sidebarLayoutElement) {
        // Check which page component to render
        const actorListElement = document.querySelector('#actor-list-app');
        const actorDetailElement = document.querySelector('#actor-detail-app');
        const apiDocsElement = document.querySelector('#api-docs-app');
        const promptValidationElement = document.querySelector('#prompt-validation-app');
        const actorsApiElement = document.querySelector('#actors-api-app');

        let pageComponent = null;
        let pageProps = {};
        let pageTitle = 'Dashboard';

        if (actorListElement) {
            pageComponent = ActorList;
            pageProps = {
                apiUrl: actorListElement.getAttribute('data-api-url'),
                csrfToken: actorListElement.getAttribute('data-csrf-token'),
                submitUrl: actorListElement.getAttribute('data-submit-url')
            };
            pageTitle = 'All Actors';
        } else if (actorDetailElement) {
            pageComponent = ActorDetail;
            pageProps = {
                uuid: actorDetailElement.getAttribute('data-uuid'),
                backUrl: actorDetailElement.getAttribute('data-back-url')
            };
            pageTitle = 'Actor Details';
        } else if (apiDocsElement) {
            pageComponent = ApiIndex;
            pageProps = {};
            pageTitle = 'API Documentation';
        } else if (promptValidationElement) {
            pageComponent = PromptValidation;
            pageProps = {};
            pageTitle = 'Prompt Validation API';
        } else if (actorsApiElement) {
            pageComponent = ActorsApi;
            pageProps = {};
            pageTitle = 'Actors API';
        }

        const app = createApp({
            render() {
                return h(SidebarLayout, { pageTitle }, {
                    default: () => pageComponent ? h(pageComponent, pageProps) : null
                });
            }
        });

        app.mount(sidebarLayoutElement);
        return;
    }

    // Fallback: Mount individual components if sidebar layout not found
    // Mount ActorForm if present
    const actorFormElement = document.querySelector('#actor-form-app');
    if (actorFormElement) {
        const props = {
            csrfToken: actorFormElement.getAttribute('data-csrf-token'),
            cancelUrl: actorFormElement.getAttribute('data-cancel-url'),
            submitUrl: actorFormElement.getAttribute('data-submit-url')
        };
        const app = createApp(ActorForm, props);
        app.mount(actorFormElement);
    }

    // Mount ActorList if present
    const actorListElement = document.querySelector('#actor-list-app');
    if (actorListElement) {
        const props = {
            createUrl: actorListElement.getAttribute('data-create-url'),
            apiUrl: actorListElement.getAttribute('data-api-url')
        };
        const app = createApp(ActorList, props);
        app.mount(actorListElement);
    }
});
