﻿// <auto-generated />
using Graham.DataAccess;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Infrastructure;
using Microsoft.EntityFrameworkCore.Metadata;
using Microsoft.EntityFrameworkCore.Migrations;
using Microsoft.EntityFrameworkCore.Storage.ValueConversion;

namespace Graham.DataAccess.Migrations
{
    [DbContext(typeof(GrahamContext))]
    [Migration("20190318212648_AddInstagramAccountValidationInProgressColumn")]
    partial class AddInstagramAccountValidationInProgressColumn
    {
        protected override void BuildTargetModel(ModelBuilder modelBuilder)
        {
#pragma warning disable 612, 618
            modelBuilder
                .HasAnnotation("ProductVersion", "2.2.3-servicing-35854")
                .HasAnnotation("Relational:MaxIdentifierLength", 128)
                .HasAnnotation("SqlServer:ValueGenerationStrategy", SqlServerValueGenerationStrategy.IdentityColumn);

            modelBuilder.Entity("Graham.DataAccess.Model.InstagramAccount", b =>
                {
                    b.Property<long>("Id")
                        .ValueGeneratedOnAdd()
                        .HasAnnotation("SqlServer:ValueGenerationStrategy", SqlServerValueGenerationStrategy.IdentityColumn);

                    b.Property<string>("Password")
                        .IsRequired();

                    b.Property<string>("Username")
                        .IsRequired();

                    b.Property<bool>("Validated");

                    b.Property<bool>("ValidationInProgress");

                    b.HasKey("Id");

                    b.ToTable("InstagramAccounts");
                });
#pragma warning restore 612, 618
        }
    }
}
