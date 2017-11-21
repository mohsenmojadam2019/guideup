import React, { Component } from 'react'
import createBrowserHistory from 'history'
import { Switch, Route, Redirect } from 'react-router-dom'
import NotificationSystem from 'react-notification-system'

import Header from '../../components/Header/'
import Sidebar from '../../components/Sidebar/'
import Breadcrumb from '../../components/Breadcrumb/'
import Aside from '../../components/Aside/'
import Footer from '../../components/Footer/'

import Dashboard from '../../views/Dashboard/'
import Places from '../../views/Places/'
import PlaceEdit from '../../views/Places/Place-edit'
import Users from '../../views/Users/'
import UserEdit from '../../views/Users/User-edit'
import Guides from '../../views/Guides/'
import GuideEdit from '../../views/Guides/Guide-edit'
import Feedbacks from '../../views/Feedbacks/'

class Full extends Component {
  
  constructor() {
    super();
  }
  _notificationSystem = {};

  showNotification = (options) => {
    return this._notificationSystem.addNotification(options);
  }

  removeNotification = (notification) => {
    this._notificationSystem.removeNotification(notification);
  }
  clearNotifications = () => {
    this._notificationSystem.clearNotifications();
  }

  componentDidMount() {
    
    if(!localStorage.getItem('token')) {
      this.props.history.push('/login');
      return;
    }    

    this._notificationSystem = this.refs.notificationSystem;
  }

  render() {

    const props = {
      ...this.props, 
      showNotification: this.showNotification, 
      removeNotification: this.removeNotification, 
      clearNotifications: this.clearNotifications
    }

    return (
      <div className="app">
        <Header history={this.props.history} />
        <div className="app-body">
          <Sidebar {...props} />
          <main className="main">
            <Breadcrumb />
            <div className="container-fluid">
              <NotificationSystem ref="notificationSystem" allowHTML={true}/>
              <Switch {...props}>
                <Route path="/dashboard" name="Dashboard" component={(routeProps) => <Dashboard {...props} {...routeProps} />} />
                
                <Route path="/place/edit/:id" name="Edit Place" component={(routeProps) => <PlaceEdit {...props} {...routeProps} />} />
                <Route path="/place/new" name="New Place" component={(routeProps) => <PlaceEdit {...props} {...routeProps} />} />
                <Route path="/place" name="Places" component={(routeProps) => <Places {...props} {...routeProps} />} />

                <Route path="/user/edit/:id" name="Edit User" component={(routeProps) => <UserEdit {...props} {...routeProps} />} />
                <Route path="/user/new" name="New user" component={(routeProps) => <UserEdit {...props} {...routeProps} />} />
                <Route path="/user" name="Users" component={(routeProps) => <Users {...props} {...routeProps} />} />

                <Route path="/guide/edit/:id" name="Edit Guide" component={(routeProps) => <GuideEdit {...props} {...routeProps} />} />
                <Route path="/guide/new" name="New Guide" component={(routeProps) => <GuideEdit {...props} {...routeProps} />} />
                <Route path="/guide" name="Guides" component={(routeProps) => <Guides {...props} {...routeProps} />} />
                
                <Route path="/feedback" name="Feedback" component={(routeProps) => <Feedbacks {...props} {...routeProps} />} />
                
                <Redirect from="/" to="/dashboard"/>
              </Switch>
            </div>
          </main>
          <Aside />
        </div>
        <Footer />
      </div>
    );
  }
}

export default Full;
