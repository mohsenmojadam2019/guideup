import React, { Component } from 'react';
import axios from 'axios';

const LOGIN_URL = 'https://guideup.com.br';

class Login extends Component {

  constructor(props) {
    super(props);

    this.state = {
      login: '',
      password: '',
      error: '',
      loading: false
    }

    this.handleInputChanged = this.handleInputChanged.bind(this);
    this.handleLoginClick = this.handleLoginClick.bind(this);
  }

  handleInputChanged(e) {
    this.setState({ [e.target.name]: e.target.value });
  }

  handleLoginClick(e) {
    if(this.state.login !== '' && this.state.password !== '') {
      const request = axios.post(`${LOGIN_URL}/oauth/token`, {
        username: this.state.login,
        password: this.state.password,
        client_id: 2,
        client_secret: 'ctGXs22X9TaU9MdpqnPxN2euFs63a26FzPimXXUz',
        grant_type: 'password'
      },
    {      
      headers: {
        'Accept': 'application/json' 
      }
    });

    this.setState({ loading: true });

      request.then(response => {
        localStorage.setItem('token', response.data.access_token);
        localStorage.setItem('expires', response.data.expires_in);
        localStorage.setItem('refreshToken', response.data.refresh_token);
        this.props.history.push('/');
      })
      request.catch(error => {
        this.setState({ error: 'Usuário ou senha inválidos', loading: false })
        console.log('====================================');
        console.log('Login error', error);
        console.log('====================================');
      })

      return;
    }

    this.setState({error: 'Informe um login e uma senha'})
  }

  render() {
    return (
      <div className="app flex-row align-items-center">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-md-8 col-lg-5">
              <img className="col-md-12 text-center" src={'img/logo-horizontal.png'} />
              <div className="row">&nbsp;</div>
              <div className="card-group mb-0">
                <div className="card card-inverse card-primary">
                  <div className="card-block">
                    <h1>Login</h1>
                    <p className="text-muted">Entre com os dados abaixo para acessar o Guide Up</p>
                    <div className="input-group mb-3">
                      <span className="input-group-addon"><i className="icon-user"></i></span>
                      <input type="text" name="login" className="form-control" placeholder="Email" value={this.state.login} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>
                    <div className="input-group mb-4">
                      <span className="input-group-addon"><i className="icon-lock"></i></span>
                      <input type="password" name="password" className="form-control" placeholder="Senha" value={this.state.password} onChange={this.handleInputChanged} disabled={this.state.loading}/>
                    </div>
                    <p className="text-danger">{this.state.error}</p>
                    <div className="row">
                      <div className="col-6">
                        {this.state.loading 
                          ? <div><i className="fa fa-refresh fa-spin"></i> Entrando ...</div>
                          : <button type="button" className="btn btn-primary active px-4" onClick={this.handleLoginClick}>Entrar</button>
                        }
                        
                      </div>
                      <div className="col-6 text-right">
                        <button type="button" className="btn btn-link text-muted px-0" onClick={e => {this.props.history.push('/password/forgot')} }>Esqueceu sua senha?</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default Login;
