@extends('layouts.app')

@section('title', 'My Todos')

@section('content')
    <h1>My Todos</h1>

    <form id="todo-create-form" method="POST" action="{{ route('todos.store') }}">
        @csrf

        <div>
            <label for="todo-title">Title</label>
            <input id="todo-title" name="title" type="text" maxlength="255" required>
            <p id="todo-title-error" role="alert" hidden></p>
        </div>

        <button id="todo-submit" type="submit">Add todo</button>
        <p id="todo-create-status" role="status" aria-live="polite"></p>
    </form>

    <p id="todos-loading" role="status">Loading todos...</p>
    <div id="todos-list" hidden></div>
    <p id="todos-empty" hidden>You do not have any todos yet.</p>
    <div id="todos-error" role="alert" hidden>
        <p id="todos-error-message">Unable to load your todos. Please try again.</p>
        <button id="todos-retry" type="button">Retry</button>
    </div>

    <script>
        const loadingState = document.getElementById('todos-loading');
        const listState = document.getElementById('todos-list');
        const emptyState = document.getElementById('todos-empty');
        const errorState = document.getElementById('todos-error');
        const errorMessage = document.getElementById('todos-error-message');
        const retryButton = document.getElementById('todos-retry');
        const createForm = document.getElementById('todo-create-form');
        const titleError = document.getElementById('todo-title-error');
        const createStatus = document.getElementById('todo-create-status');
        const submitButton = document.getElementById('todo-submit');
        let todos = [];
        let isCreatingTodo = false;
        const loginUrl = @json(route('login'));
        const editUrlTemplate = @json(route('todos.edit', ['todo' => '__TODO_ID__']));
        const deleteUrlTemplate = @json(route('todos.destroy', ['todo' => '__TODO_ID__']));

        function messageForStatus(status, action) {
            if (status === 401) {
                return 'Your session has ended. Redirecting to login...';
            }

            if (status === 403 || status === 404) {
                return `This todo is unavailable or you do not have permission to ${action} it.`;
            }

            if (status === 419) {
                return 'Your page session has expired. Refresh the page and try again.';
            }

            if (status === 500) {
                return 'The server encountered an unexpected error. Please try again.';
            }

            return `Unable to ${action} the todo. Please try again.`;
        }

        function handleExpiredAuthentication(response) {
            if (response.status !== 401) {
                return false;
            }

            window.location.assign(loginUrl);

            return true;
        }

        function showState(state) {
            loadingState.hidden = state !== 'loading';
            listState.hidden = state !== 'list';
            emptyState.hidden = state !== 'empty';
            errorState.hidden = state !== 'error';
        }

        function renderTodos(items) {
            listState.replaceChildren();

            for (const todo of items) {
                const article = document.createElement('article');
                const title = document.createElement('h2');
                const status = document.createElement('p');
                const editLink = document.createElement('a');
                const deleteButton = document.createElement('button');

                // textContent renders user-controlled titles as text instead of executable HTML.
                title.textContent = todo.title;
                status.textContent = todo.completed ? 'Completed' : 'Pending';
                editLink.href = editUrlTemplate.replace('__TODO_ID__', encodeURIComponent(todo.id));
                editLink.textContent = 'Edit';
                deleteButton.type = 'button';
                deleteButton.textContent = 'Delete';
                deleteButton.dataset.todoId = todo.id;
                deleteButton.addEventListener('click', () => deleteTodo(todo, deleteButton));
                article.append(title, status, editLink, document.createTextNode(' '), deleteButton);
                listState.append(article);
            }
        }

        async function deleteTodo(todo, deleteButton) {
            if (!window.confirm(`Delete "${todo.title}"?`)) {
                return;
            }

            deleteButton.disabled = true;
            deleteButton.textContent = 'Deleting...';
            createStatus.textContent = '';

            try {
                const response = await fetch(
                    deleteUrlTemplate.replace('__TODO_ID__', encodeURIComponent(todo.id)),
                    {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': createForm.querySelector('[name="_token"]').value,
                        },
                    },
                );

                if (handleExpiredAuthentication(response)) {
                    createStatus.textContent = messageForStatus(response.status, 'delete');
                    return;
                }

                if (!response.ok) {
                    createStatus.textContent = messageForStatus(response.status, 'delete');
                    return;
                }

                todos = todos.filter((item) => item.id !== todo.id);
                renderTodos(todos);
                showState(todos.length === 0 ? 'empty' : 'list');
                createStatus.textContent = 'Todo deleted successfully.';
            } catch (error) {
                createStatus.textContent = 'Unable to delete the todo. Check your connection and try again.';
            } finally {
                deleteButton.disabled = false;
                deleteButton.textContent = 'Delete';
            }
        }

        async function loadTodos() {
            showState('loading');

            try {
                const response = await fetch(@json(route('todos.data')), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (handleExpiredAuthentication(response)) {
                    errorMessage.textContent = messageForStatus(response.status, 'load');
                    showState('error');
                    return;
                }

                if (!response.ok) {
                    errorMessage.textContent = messageForStatus(response.status, 'load');
                    showState('error');
                    return;
                }

                const payload = await response.json();
                todos = payload.data;
                renderTodos(todos);
                showState(todos.length === 0 ? 'empty' : 'list');
            } catch (error) {
                errorMessage.textContent = 'Unable to load your todos. Check your connection and try again.';
                showState('error');
            }
        }

        async function createTodo(event) {
            event.preventDefault();

            if (isCreatingTodo) {
                return;
            }

            isCreatingTodo = true;
            submitButton.disabled = true;
            titleError.hidden = true;
            titleError.textContent = '';
            createStatus.textContent = 'Creating...';

            try {
                const response = await fetch(createForm.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                    body: new FormData(createForm),
                });

                if (handleExpiredAuthentication(response)) {
                    createStatus.textContent = messageForStatus(response.status, 'create');
                    return;
                }

                if (response.status === 422) {
                    const payload = await response.json();
                    const message = payload.errors?.title?.[0];

                    if (message) {
                        titleError.textContent = message;
                        titleError.hidden = false;
                    }

                    createStatus.textContent = 'Please correct the highlighted fields.';

                    return;
                }

                if (!response.ok) {
                    createStatus.textContent = messageForStatus(response.status, 'create');
                    return;
                }

                const payload = await response.json();
                todos.unshift(payload.data);
                renderTodos(todos);
                showState('list');
                createForm.reset();
                createStatus.textContent = 'Todo created successfully.';
            } catch (error) {
                createStatus.textContent = 'Unable to create the todo. Check your connection and try again.';
            } finally {
                isCreatingTodo = false;
                submitButton.disabled = false;
            }
        }

        retryButton.addEventListener('click', loadTodos);
        createForm.addEventListener('submit', createTodo);
        loadTodos();
    </script>
@endsection
