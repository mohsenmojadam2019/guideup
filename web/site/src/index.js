import React from 'react'
import ReactDOM from 'react-dom'
import { Router , Route, Switch } from 'react-router-dom'
import { createBrowserHistory } from 'history'

// Containers
import Full from './containers/Full/'
import Login from './containers/Login/'
import ResetPassword from './containers/Password/ResetPassword'
import ForgotPassword from './containers/Password/ForgotPassword'

function render () {
  const history = createBrowserHistory();
  return (
    <Router  history={history}>
      <Switch>
        <Route path="/password/forgot" name="ForgotPassword" component={ForgotPassword}/>
        <Route path="/password/reset/:token" name="ResetPassword" component={ResetPassword}/>
        <Route path="/login" name="Login" component={Login}/>
        <Route path="/" name="Home" component={Full} />
      </Switch>
    </Router >
  )
}

ReactDOM.render(render(), document.getElementById('root'))
