using AutoMapper;
using Graham.DataAccess;
using Graham.Services;
using Graham.Services.Configuration;
using Graham.Services.Interfaces;
using Graham.WebAPI.Extensions;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using NServiceBus;
using Swashbuckle.AspNetCore.Swagger;

namespace Graham.WebAPI
{
    public class Startup
    {
        public Startup(IConfiguration configuration)
        {
            Configuration = configuration;
        }

        public IConfiguration Configuration { get; }

        // This method gets called by the runtime. Use this method to add services to the container.
        public void ConfigureServices(IServiceCollection services)
        {
            var endpointConfiguration = new EndpointConfiguration(Configuration["ApplicationName"]);
            endpointConfiguration.ConfigureTransport();
            endpointConfiguration.SendOnly();

            var endpointInstance = Endpoint.Start(endpointConfiguration).GetAwaiter().GetResult();
            services.AddSingleton<IMessageSession>(endpointInstance);

            services.AddAutoMapper();
            services.AddOptions();
            services.AddMvc().SetCompatibilityVersion(CompatibilityVersion.Version_2_1);
            services.AddScoped<IInstagramAccountService, InstagramAccountService>();
            services.Configure<AppSettings>(Configuration.GetSection("AppSettings"));
            services.AddDbContext<GrahamContext>(options => options.UseSqlServer(Configuration.GetConnectionString("GrahamContext")));
            services.AddSwaggerGen(c =>
            {
                c.SwaggerDoc("v1", new Info { Title = "Graham.WebAPI", Version = "v1" });
            });
        }

        // This method gets called by the runtime. Use this method to configure the HTTP request pipeline.
        public void Configure(IApplicationBuilder app, IHostingEnvironment env)
        {
            if (env.IsDevelopment())
            {
                app.UseDeveloperExceptionPage();
            }

            app.UseSwagger();

            app.UseSwaggerUI(c =>
            {
                c.SwaggerEndpoint("/swagger/v1/swagger.json", "Graham.WebAPI V1");
            });

            app.UseMvc();
        }
    }
}
