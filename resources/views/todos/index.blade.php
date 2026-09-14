@extends('layouts.app')

@section('title', 'My Todos')

@section('content')
    <h1>My Todos</h1>

    <p id="todos-loading" role="status">Loading todos...</p>
    <div id="todos-list" hidden></div>
    <p id="todos-empty" hidden>You do not have any todos yet.</p>
    <div id="todos-error" role="alert" hidden>
        <p>Unable to load your todos. Please try again.</p>
        <button id="todos-retry" type="button">Retry</button>
    </div>

    <script>
        const loadingState = document.getElementById('todos-loading');
        const listState = document.getElementById('todos-list');
        const emptyState = document.getElementById('todos-empty');
        const errorState = document.getElementById('todos-error');
        const retryButton = document.getElementById('todos-retry');

        function showState(state) {
            loadingState.hidden = state !== 'loading';
            listState.hidden = state !== 'list';
            emptyState.hidden = state !== 'empty';
            errorState.hidden = state !== 'error';
        }

        function renderTodos(todos) {
            listState.replaceChildren();

            for (const todo of todos) {
                const article = document.createElement('article');
                const title = document.createElement('h2');
                const status = document.createElement('p');

                // textContent renders user-controlled titles as text instead of executable HTML.
                title.textContent = todo.title;
                status.textContent = todo.completed ? 'Completed' : 'Pending';
                article.append(title, status);
                listState.append(article);
            }
        }

        async function loadTodos() {
            showState('loading');

            try {
                const response = await fetch(@json(route('todos.data')), {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Todo request failed with status ${response.status}`);
                }

                const payload = await response.json();
                renderTodos(payload.data);
                showState(payload.data.length === 0 ? 'empty' : 'list');
            } catch (error) {
                showState('error');
            }
        }

        retryButton.addEventListener('click', loadTodos);
        loadTodos();
    </script>
@endsection
